<?php

namespace App\Livewire\Concerns;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing;
use Illuminate\Validation\Rule;

/** Shared "record payment" sheet used on the Fees page and the student profile. */
trait RecordsPayments
{
    public ?int $payingInvoiceId = null;

    public string $payAmount = '';

    public ?string $payDate = null;

    public string $payMethod = 'upi';

    public string $payReference = '';

    public ?int $lastPaymentId = null;

    public function startPayment(int $invoiceId): void
    {
        $invoice = Invoice::with('payments')->findOrFail($invoiceId);

        $this->resetValidation();
        $this->payingInvoiceId = $invoice->id;
        $this->payAmount = (string) round($invoice->balance(), 2);
        $this->payDate = now()->toDateString();
        $this->payMethod = 'upi';
        $this->payReference = '';
        $this->lastPaymentId = null;
    }

    public function cancelPayment(): void
    {
        $this->payingInvoiceId = null;
        $this->lastPaymentId = null;
    }

    public function savePayment(Billing $billing): void
    {
        $invoice = Invoice::with('payments')->findOrFail($this->payingInvoiceId);

        $this->validate([
            'payAmount' => ['required', 'numeric', 'min:1', 'max:'.max(1, $invoice->balance())],
            'payDate' => 'required|date|before_or_equal:today',
            'payMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'payReference' => 'nullable|string|max:100',
        ], [
            'payAmount.max' => 'That is more than the amount due.',
        ]);

        $payment = $billing->recordPayment($invoice, (float) $this->payAmount, $this->payDate, $this->payMethod, $this->payReference);

        $this->lastPaymentId = $payment->id;
    }

    public function waive(int $invoiceId): void
    {
        Invoice::findOrFail($invoiceId)->update(['status' => InvoiceStatus::Waived]);
    }

    public function lastPayment(): ?Payment
    {
        return $this->lastPaymentId ? Payment::with('invoice.student.guardian')->find($this->lastPaymentId) : null;
    }
}
