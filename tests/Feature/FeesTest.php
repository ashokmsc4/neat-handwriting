<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Livewire\Fees\Index;
use App\Livewire\Students\Show;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FeePlan;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class FeesTest extends TestCase
{
    use RefreshDatabase;

    private FeePlan $monthly;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 10:00');
        $this->actingAs(User::factory()->create());

        $this->monthly = FeePlan::create(['name' => 'Monthly', 'type' => 'monthly', 'amount' => 1500]);
        $batch = Batch::create(['course_id' => Course::create(['name' => 'Phonics'])->id, 'name' => 'Sounds A', 'fee_plan_id' => $this->monthly->id]);
        $this->student = Student::factory()->create();
        Enrollment::create(['student_id' => $this->student->id, 'batch_id' => $batch->id, 'fee_plan_id' => $this->monthly->id, 'start_date' => '2026-09-01']);
    }

    public function test_monthly_billing_creates_one_invoice_per_month(): void
    {
        $billing = app(Billing::class);

        $this->assertSame(1, $billing->generateMonthly(now()->startOfMonth()));
        $this->assertSame(0, $billing->generateMonthly(now()->startOfMonth()));

        $invoice = Invoice::sole();
        $this->assertSame('Oct 2026', $invoice->period_label);
        $this->assertSame('2026-10-10', $invoice->due_date->toDateString());
        $this->assertEquals(1500, $invoice->amount);
    }

    public function test_billing_skips_paused_students_and_non_monthly_plans(): void
    {
        $this->student->update(['status' => 'paused']);
        $other = Student::factory()->create();
        $pack = FeePlan::create(['name' => 'Pack', 'type' => 'pack', 'amount' => 2000, 'classes_count' => 12]);
        Enrollment::create(['student_id' => $other->id, 'batch_id' => Batch::first()->id, 'fee_plan_id' => $pack->id, 'start_date' => '2026-09-01']);

        $this->assertSame(0, app(Billing::class)->generateMonthly(now()->startOfMonth()));
    }

    public function test_bill_command_and_button(): void
    {
        $this->artisan('fees:bill', ['month' => '2026-09'])->expectsOutputToContain('Created 1 invoice(s) for September 2026')->assertSuccessful();

        Livewire::test(Index::class)->call('billThisMonth');

        $this->assertEqualsCanonicalizing(['2026-09', '2026-10'], Invoice::pluck('period')->all());
    }

    public function test_partial_then_full_payment_with_receipts(): void
    {
        app(Billing::class)->generateMonthly(now()->startOfMonth());
        $invoice = Invoice::sole();

        Livewire::test(Index::class)
            ->call('startPayment', $invoice->id)
            ->assertSet('payAmount', '1500')
            ->set('payAmount', '1000')
            ->set('payMethod', 'cash')
            ->call('savePayment')
            ->assertHasNoErrors()
            ->assertSee('received');

        $this->assertSame(InvoiceStatus::Partial, $invoice->fresh()->status);

        Livewire::test(Index::class)
            ->call('startPayment', $invoice->id)
            ->assertSet('payAmount', '500')
            ->call('savePayment');

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertEquals(['NH-2026-0001', 'NH-2026-0002'], Payment::orderBy('id')->pluck('receipt_no')->all());

        $this->get(route('payments.receipt', Payment::first()))->assertOk()->assertSee('NH-2026-0001')->assertSee($this->student->name);
    }

    public function test_cannot_pay_more_than_due(): void
    {
        app(Billing::class)->generateMonthly(now()->startOfMonth());

        Livewire::test(Index::class)
            ->call('startPayment', Invoice::sole()->id)
            ->set('payAmount', '5000')
            ->call('savePayment')
            ->assertHasErrors('payAmount');

        $this->assertSame(0, Payment::count());
    }

    public function test_extra_charge_with_discount_and_waive_from_profile(): void
    {
        Setting::put(['fee_due_day' => 15]);

        Livewire::test(Show::class, ['student' => $this->student])
            ->set('tab', 'fees')
            ->call('startCharge')
            ->assertSet('chargeDue', '2026-10-15')
            ->set('chargeLabel', 'Workbook')
            ->set('chargeAmount', '400')
            ->set('chargeDiscount', '100')
            ->call('saveCharge')
            ->assertHasNoErrors();

        $invoice = Invoice::sole();
        $this->assertEquals(300, $invoice->balance());

        Livewire::test(Show::class, ['student' => $this->student])->call('waive', $invoice->id);

        $this->assertSame(InvoiceStatus::Waived, $invoice->fresh()->status);
        $this->assertEquals(0, $invoice->fresh()->balance());
    }

    public function test_fees_pages_render(): void
    {
        app(Billing::class)->generateMonthly(now()->startOfMonth());

        $this->get('/fees')->assertOk()->assertSee($this->student->name)->assertSee('₹1,500');
        $this->get('/fees?tab=paid')->assertOk();
        $this->get(route('students.show', [$this->student, 'tab' => 'fees']))->assertOk()->assertSee('Oct 2026');
    }
}
