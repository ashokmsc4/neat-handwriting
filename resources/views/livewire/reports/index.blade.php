@php($range = ['from' => $start->toDateString(), 'to' => $end->toDateString()])

<div class="space-y-4">
    <x-back-header :href="route('more')" title="Reports">
        <input wire:model.live="month" type="month" max="{{ now()->format('Y-m') }}" class="input w-auto shrink-0" aria-label="Month">
    </x-back-header>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card stat">
            <span class="stat-label">Attendance</span>
            <span class="stat-value">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</span>
        </div>
        <div class="card stat">
            <span class="stat-label">Collected</span>
            <span class="stat-value text-success">{{ $currency }}{{ number_format($collections->last()['total']) }}</span>
        </div>
        <div class="card stat">
            <span class="stat-label">Outstanding now</span>
            <span class="stat-value text-danger">{{ $currency }}{{ number_format($outstanding) }}</span>
        </div>
        <div class="card stat">
            <span class="stat-label">New students</span>
            <span class="stat-value">{{ $joined }}</span>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card p-4 lg:p-6">
            <h2 class="section-title">Fees collected, last 6 months</h2>
            <div class="flex h-40 items-end gap-2" role="img" aria-label="Fees collected per month">
                @foreach ($collections as $c)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-[11px] text-muted">{{ $c['total'] ? number_format($c['total'] / 1000, 1).'k' : '' }}</span>
                        <div class="w-full max-w-10 rounded-t-md bg-brand" style="height: {{ max(2, round($c['total'] / $maxCollection * 110)) }}px" title="{{ $currency }}{{ number_format($c['total']) }}"></div>
                        <span class="text-xs text-muted">{{ $c['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card p-4 lg:p-6">
            <h2 class="section-title">Attendance by batch, {{ $start->format('F') }}</h2>
            @forelse ($batches as $b)
                <div class="py-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-ink">{{ $b['name'] }}</span>
                        <span class="text-muted">{{ $b['rate'] ?? '—' }}% · {{ $b['classes'] }} {{ Str::plural('class', $b['classes']) }}</span>
                    </div>
                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-2">
                        <div class="h-full rounded-full bg-success" style="width: {{ $b['rate'] ?? 0 }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted">No classes recorded this month.</p>
            @endforelse
        </section>

        <section class="card p-4 lg:p-6">
            <h2 class="section-title">Students</h2>
            <dl class="grid grid-cols-3 gap-3 text-center">
                @foreach ($statusCounts as $label => $count)
                    <div class="rounded-xl bg-surface-2 p-3">
                        <dt class="text-sm text-muted">{{ $label }}</dt>
                        <dd class="text-xl font-semibold text-ink">{{ $count }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card p-4 lg:p-6">
            <h2 class="section-title">Download as spreadsheet (CSV)</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                <a href="{{ route('exports', ['type' => 'students']) }}" class="btn-secondary">All students</a>
                <a href="{{ route('exports', ['type' => 'attendance'] + $range) }}" class="btn-secondary">Attendance · {{ $start->format('M') }}</a>
                <a href="{{ route('exports', ['type' => 'payments'] + $range) }}" class="btn-secondary">Payments · {{ $start->format('M') }}</a>
                <a href="{{ route('exports', ['type' => 'outstanding']) }}" class="btn-secondary">Fees outstanding</a>
            </div>
            <p class="mt-2 text-xs text-muted">Opens in Excel, Numbers or Google Sheets.</p>
        </section>
    </div>
</div>
