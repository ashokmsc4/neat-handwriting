<div class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title">Fees</h1>
        <button type="button" wire:click="billThisMonth" wire:loading.attr="disabled" class="btn-secondary">
            Bill {{ now()->format('F') }}
        </button>
    </header>

    <section class="grid grid-cols-2 gap-3">
        <div class="card stat">
            <span class="stat-label">Outstanding</span>
            <span class="stat-value text-danger">{{ $currency }}{{ number_format($totalDue) }}</span>
        </div>
        <div class="card stat">
            <span class="stat-label">Collected in {{ now()->format('F') }}</span>
            <span class="stat-value text-success">{{ $currency }}{{ number_format($collectedThisMonth) }}</span>
        </div>
    </section>

    <div class="flex flex-col gap-2 sm:flex-row">
        <nav class="segmented sm:w-72" aria-label="Fees view">
            <label><input type="radio" wire:model.live="tab" value="due"><span>Due ({{ $invoices->count() }})</span></label>
            <label><input type="radio" wire:model.live="tab" value="paid"><span>Paid this month</span></label>
        </nav>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search student" class="input flex-1">
    </div>

    @if ($tab === 'due')
        <div class="card divide-y divide-line">
            @forelse ($invoices as $invoice)
                @php($balance = $invoice->balance())
                @php($guardian = $invoice->student->guardian)
                <div wire:key="invoice-{{ $invoice->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 items-start justify-between gap-3">
                        <a href="{{ route('students.show', [$invoice->student, 'tab' => 'fees']) }}" wire:navigate class="min-w-0">
                            <div class="truncate font-medium text-ink">{{ $invoice->student->name }}</div>
                            <div class="text-sm text-muted">
                                {{ $invoice->period_label }} · due {{ $invoice->due_date->format('j M') }}
                                @if ($invoice->status->value === 'partial') · part paid @endif
                                @if ($invoice->due_date->isPast()) <span class="text-danger">· overdue</span> @endif
                            </div>
                        </a>
                        <div class="shrink-0 font-semibold text-ink">{{ $currency }}{{ number_format($balance) }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:flex">
                        @if ($guardian)
                            @php($message = "Hi {$guardian->name}, a gentle reminder that {$invoice->student->name}'s fee of {$currency}".number_format($balance)." for {$invoice->period_label} is due on {$invoice->due_date->format('j M')}. Thank you! - ".\App\Models\Setting::get('school_name'))
                            <a href="https://wa.me/{{ $guardian->whatsappNumber() }}?text={{ rawurlencode($message) }}" target="_blank" rel="noopener" class="btn-secondary">Remind</a>
                        @endif
                        <button type="button" wire:click="startPayment({{ $invoice->id }})" class="btn-primary">Mark paid</button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-muted">Nothing outstanding. 🎉</div>
            @endforelse
        </div>
    @else
        <div class="card divide-y divide-line">
            @forelse ($payments as $payment)
                <div wire:key="payment-{{ $payment->id }}" class="flex items-center gap-3 p-4">
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-ink">{{ $payment->invoice->student->name }}</div>
                        <div class="text-sm text-muted">{{ $payment->paid_on->format('j M') }} · {{ $payment->method->label() }} · {{ $payment->invoice->period_label }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold text-success">{{ $currency }}{{ number_format($payment->amount) }}</div>
                        <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="text-xs text-brand">{{ $payment->receipt_no }}</a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-muted">No payments recorded this month yet.</div>
            @endforelse
        </div>
    @endif

    @include('partials.payment-sheet')
</div>
