{{-- Bottom sheet on phones, centred dialog on larger screens. Needs the RecordsPayments trait. --}}
@if ($payingInvoiceId)
    @php
        $payingInvoice = \App\Models\Invoice::with('student', 'payments')->find($payingInvoiceId);
        $done = $this->lastPayment();
        $currency = config('school.currency');
    @endphp
    <div class="fixed inset-0 z-40 flex items-end justify-center bg-ink/40 sm:items-center sm:p-4" wire:key="pay-sheet"
         x-data x-on:keydown.escape.window="$wire.cancelPayment()">
        <div class="absolute inset-0" wire:click="cancelPayment"></div>
        <div class="relative w-full max-w-md rounded-t-3xl bg-surface p-5 shadow-xl sm:rounded-3xl" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom))" role="dialog" aria-modal="true" aria-labelledby="pay-title">
            @if ($done)
                <div class="space-y-4 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-soft text-2xl text-success">✓</div>
                    <div>
                        <h2 id="pay-title" class="text-lg font-semibold text-ink">{{ $currency }}{{ number_format($done->amount) }} received</h2>
                        <p class="text-sm text-muted">Receipt {{ $done->receipt_no }} · {{ $done->invoice->student->name }}</p>
                    </div>
                    <div class="grid gap-2">
                        <a href="{{ route('payments.receipt', $done) }}" target="_blank" class="btn-secondary">View / print receipt</a>
                        @if ($guardian = $done->invoice->student->guardian)
                            @php($text = "Hi {$guardian->name}, we received {$currency}".number_format($done->amount)." for {$done->invoice->student->name} ({$done->invoice->period_label}). Receipt no. {$done->receipt_no}. Thank you! - ".\App\Models\Setting::get('school_name'))
                            <a href="https://wa.me/{{ $guardian->whatsappNumber() }}?text={{ rawurlencode($text) }}" target="_blank" rel="noopener" class="btn-secondary">Send receipt on WhatsApp</a>
                        @endif
                        <button type="button" wire:click="cancelPayment" class="btn-primary">Done</button>
                    </div>
                </div>
            @elseif ($payingInvoice)
                <form wire:submit="savePayment" class="space-y-4">
                    <div>
                        <h2 id="pay-title" class="text-lg font-semibold text-ink">Record payment</h2>
                        <p class="text-sm text-muted">{{ $payingInvoice->student->name }} · {{ $payingInvoice->period_label }} · {{ $currency }}{{ number_format($payingInvoice->balance()) }} due</p>
                    </div>

                    <div>
                        <label for="payAmount" class="label">Amount ({{ $currency }})</label>
                        <input wire:model="payAmount" id="payAmount" type="number" inputmode="decimal" step="0.01" class="input text-lg font-semibold">
                        @error('payAmount') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="label">Paid by</span>
                        <div class="segmented">
                            @foreach (\App\Enums\PaymentMethod::cases() as $m)
                                <label><input type="radio" wire:model="payMethod" value="{{ $m->value }}"><span>{{ $m->label() }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="payDate" class="label">Date</label>
                            <input wire:model="payDate" id="payDate" type="date" class="input">
                            @error('payDate') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="payReference" class="label">Ref. <span class="text-muted">(optional)</span></label>
                            <input wire:model="payReference" id="payReference" type="text" placeholder="UPI ref" class="input">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="cancelPayment" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="savePayment">Save payment</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endif
