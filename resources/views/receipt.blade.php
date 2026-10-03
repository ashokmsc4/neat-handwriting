<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_no }} · {{ $school['name'] }}</title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; background: #f6f7fb; color: #1f2937; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        .page { max-width: 560px; margin: 24px auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 32px; }
        header { display: flex; justify-content: space-between; gap: 16px; border-bottom: 2px solid #4f46e5; padding-bottom: 16px; }
        h1 { margin: 0; font-size: 22px; }
        .muted { color: #6b7280; font-size: 14px; }
        .title { text-align: right; }
        .title strong { display: block; font-size: 18px; color: #4f46e5; letter-spacing: .05em; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 15px; }
        td { padding: 10px 0; border-bottom: 1px solid #f0f0f3; }
        td:last-child { text-align: right; }
        .total td { font-size: 20px; font-weight: 700; border-bottom: 0; padding-top: 16px; }
        .actions { max-width: 560px; margin: 0 auto 24px; display: flex; gap: 8px; justify-content: flex-end; padding: 0 16px; }
        button { min-height: 44px; padding: 0 20px; border: 0; border-radius: 12px; background: #4f46e5; color: #fff; font-size: 15px; cursor: pointer; }
        @media print { body { background: #fff; } .page { border: 0; margin: 0; } .actions { display: none; } }
        @media (max-width: 600px) { .page { margin: 0; border-radius: 0; border: 0; padding: 20px; } }
    </style>
</head>
<body>
    <div class="page">
        <header>
            <div>
                <h1>{{ $school['name'] }}</h1>
                @if ($school['address']) <div class="muted">{{ $school['address'] }}</div> @endif
                @if ($school['phone']) <div class="muted">{{ $school['phone'] }}</div> @endif
            </div>
            <div class="title">
                <strong>RECEIPT</strong>
                <div class="muted">{{ $payment->receipt_no }}</div>
                <div class="muted">{{ $payment->paid_on->format('j M Y') }}</div>
            </div>
        </header>

        <table>
            <tr><td class="muted">Received from</td><td>{{ $student->guardian?->name ?? '—' }}</td></tr>
            <tr><td class="muted">Student</td><td>{{ $student->name }}</td></tr>
            <tr><td class="muted">For</td><td>{{ $invoice->period_label }}</td></tr>
            <tr><td class="muted">Paid by</td><td>{{ $payment->method->label() }}@if ($payment->reference) · {{ $payment->reference }}@endif</td></tr>
            @if ($invoice->balance() > 0)
                <tr><td class="muted">Still due on this invoice</td><td>{{ $currency }}{{ number_format($invoice->balance(), 2) }}</td></tr>
            @endif
            <tr class="total"><td>Amount received</td><td>{{ $currency }}{{ number_format($payment->amount, 2) }}</td></tr>
        </table>

        <p class="muted" style="margin-top: 32px; text-align: center">Thank you!</p>
    </div>
    <div class="actions">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>
</body>
</html>
