@php($currency = config('school.currency'))

<div class="space-y-4">
    <h1 class="page-title">Fees due</h1>

    <div class="card divide-y divide-line">
        @forelse ($invoices as $invoice)
            @php($balance = $invoice->amount - $invoice->discount - $invoice->payments->sum('amount'))
            @php($guardian = $invoice->student->guardian)
            <div wire:key="invoice-{{ $invoice->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                <div class="flex min-w-0 flex-1 items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate font-medium text-ink">{{ $invoice->student->name }}</div>
                        <div class="text-sm text-muted">
                            {{ $invoice->period_label }} · due {{ $invoice->due_date->format('j M') }}
                            @if ($invoice->due_date->isPast()) <span class="text-danger">· overdue</span> @endif
                        </div>
                    </div>
                    <div class="shrink-0 font-semibold text-ink">{{ $currency }}{{ number_format($balance) }}</div>
                </div>
                @if ($guardian)
                    @php($message = "Hi {$guardian->name}, a gentle reminder that {$invoice->student->name}'s fee of {$currency}".number_format($balance)." for {$invoice->period_label} is due. Thank you! - ".config('school.name'))
                    <a href="https://wa.me/{{ $guardian->whatsappNumber() }}?text={{ rawurlencode($message) }}" target="_blank" rel="noopener" class="btn-secondary w-full sm:w-auto">
                        Remind on WhatsApp
                    </a>
                @endif
            </div>
        @empty
            <div class="p-8 text-center text-muted">Nothing outstanding. 🎉</div>
        @endforelse
    </div>
</div>
