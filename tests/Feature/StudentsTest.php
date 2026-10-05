<?php

namespace Tests\Feature;

use App\Enums\StudentStatus;
use App\Livewire\Students\Form;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeePlan;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_pages_render(): void
    {
        Student::factory()->create(['name' => 'Aarav Sharma']);

        $this->get('/')->assertOk();
        $this->get('/students')->assertOk()->assertSee('Aarav Sharma');
        $this->get('/students/new')->assertOk();
        $this->get('/batches')->assertOk();
        $this->get('/fees')->assertOk();
    }

    public function test_teacher_can_add_a_student_with_parent(): void
    {
        Livewire::test(Form::class)
            ->set('name', 'Diya Patel')
            ->set('grade', 'UKG')
            ->set('guardian_name', 'Meera Patel')
            ->set('guardian_phone', '98765 43210')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::firstWhere('name', 'Diya Patel');
        $this->assertSame(StudentStatus::Active, $student->status);
        $this->assertSame('Meera Patel', $student->guardian->name);
        $this->assertSame('919876543210', $student->guardian->whatsappNumber());
    }

    public function test_siblings_share_one_parent_record(): void
    {
        foreach (['Kabir', 'Anya'] as $name) {
            Livewire::test(Form::class)
                ->set('name', $name)
                ->set('guardian_name', 'Ravi')
                ->set('guardian_phone', '9000000001')
                ->call('save');
        }

        $this->assertSame(1, Guardian::count());
        $this->assertSame(2, Guardian::first()->students()->count());
    }

    public function test_teacher_can_edit_a_student(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Form::class, ['student' => $student])
            ->assertSet('name', $student->name)
            ->set('status', 'paused')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(StudentStatus::Paused, $student->fresh()->status);
    }

    public function test_student_can_be_put_in_batches_from_the_form(): void
    {
        $this->seed(CurriculumSeeder::class);
        $plan = FeePlan::create(['name' => 'Monthly', 'type' => 'monthly', 'amount' => 1500]);
        $cursive = Batch::create(['course_id' => Course::first()->id, 'name' => 'Cursive A', 'fee_plan_id' => $plan->id]);
        $phonics = Batch::create(['course_id' => Course::firstWhere('name', 'Phonics')->id, 'name' => 'Phonics B']);
        Batch::create(['course_id' => Course::first()->id, 'name' => 'Old batch', 'active' => false]);

        Livewire::test(Form::class)
            ->assertSee('Cursive A')
            ->assertDontSee('Old batch')
            ->set('name', 'Diya Patel')
            ->set('guardian_name', 'Meera Patel')
            ->set('guardian_phone', '9876543210')
            ->set('batch_ids', [(string) $cursive->id, (string) $phonics->id])
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::firstWhere('name', 'Diya Patel');
        $this->assertEqualsCanonicalizing([$cursive->id, $phonics->id], $student->batches->pluck('id')->all());
        $this->assertSame($plan->id, $student->enrollments()->firstWhere('batch_id', $cursive->id)->fee_plan_id);

        // Editing shows the current batches; unticking one ends that enrollment.
        Livewire::test(Form::class, ['student' => $student])
            ->assertSet('batch_ids', [(string) $cursive->id, (string) $phonics->id])
            ->set('batch_ids', [(string) $phonics->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([$phonics->id], $student->fresh()->batches->pluck('id')->all());
        $this->assertSame('ended', $student->enrollments()->firstWhere('batch_id', $cursive->id)->status);
        $this->assertSame(2, $student->enrollments()->count());
    }

    public function test_name_and_parent_phone_are_required(): void
    {
        Livewire::test(Form::class)
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'guardian_phone' => 'required']);
    }
}
