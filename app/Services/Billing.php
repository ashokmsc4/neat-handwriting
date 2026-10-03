<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Billing
{
    /**
     * Creates this month's invoice for every active enrollment on a monthly plan.
     * Safe to run more than once: a month is only billed once per enrollment.
     */
    public function generateMonthly(Carbon $month): int
    {
        $period = $month->format('Y-m');
        $dueDay = min((int) Setting::get('fee_due_day'), $month->daysInMonth);
        $created = 0;

        Enrollment::query()
            ->where('status', 'active')
            ->where('start_date', '<=', $month->copy()->endOfMonth())
            ->whereHas('feePlan', fn ($q) => $q->where('type', 'monthly'))
            ->whereHas('student', fn ($q) => $q->active())
            ->whereDoesntHave('invoices', fn ($q) => $q->where('period', $period))
            ->with('feePlan')
            ->each(function (Enrollment $enrollment) use ($month, $period, $dueDay, &$created) {
                Invoice::create([
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'period_label' => $month->format('M Y'),
                    'period' => $period,
                    'due_date' => $month->copy()->day($dueDay),
                    'amount' => $enrollment->feePlan->amount,
                    'status' => InvoiceStatus::Due,
                ]);
                $created++;
            });

        return $created;
    }

    public function recordPayment(Invoice $invoice, float $amount, string $paidOn, PaymentMethod|string $method, ?string $reference = null): Payment
    {
        return DB::transaction(function () use ($invoice, $amount, $paidOn, $method, $reference) {
            $payment = $invoice->payments()->create([
                'amount' => $amount,
                'paid_on' => $paidOn,
                'method' => $method,
                'reference' => $reference ?: null,
                'receipt_no' => $this->nextReceiptNo(Carbon::parse($paidOn)),
            ]);

            $this->refreshStatus($invoice);

            return $payment;
        });
    }

    public function refreshStatus(Invoice $invoice): void
    {
        if ($invoice->status === InvoiceStatus::Waived) {
            return;
        }

        $paid = (float) $invoice->payments()->sum('amount');
        $owed = (float) $invoice->amount - (float) $invoice->discount;

        $invoice->update([
            'status' => match (true) {
                $paid >= $owed => InvoiceStatus::Paid,
                $paid > 0 => InvoiceStatus::Partial,
                default => InvoiceStatus::Due,
            },
        ]);
    }

    /** e.g. NH-2026-0007, numbered per calendar year. */
    private function nextReceiptNo(Carbon $date): string
    {
        $prefix = Setting::get('receipt_prefix').'-'.$date->year.'-';
        $last = Payment::where('receipt_no', 'like', $prefix.'%')->lockForUpdate()->max('receipt_no');
        $next = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
