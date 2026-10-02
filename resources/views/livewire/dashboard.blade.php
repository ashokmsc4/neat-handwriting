<div class="space-y-6">
    <header>
        <p class="text-sm text-muted">{{ $today->format('l, j F') }}</p>
        <h1 class="page-title">Good {{ $today->hour < 12 ? 'morning' : ($today->hour < 17 ? 'afternoon' : 'evening') }}, {{ strtok(auth()->user()->name, ' ') }}</h1>
    </header>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <a href="{{ route('students.index') }}" wire:navigate class="card stat">
            <span class="stat-label">Active students</span>
            <span class="stat-value">{{ $activeStudents }}</span>
        </a>
        <a href="{{ route('batches.index') }}" wire:navigate class="card stat">
            <span class="stat-label">Batches</span>
            <span class="stat-value">{{ $activeBatches }}</span>
        </a>
        <a href="{{ route('fees.index') }}" wire:navigate class="card stat">
            <span class="stat-label">Fees outstanding</span>
            <span class="stat-value">{{ config('school.currency') }}{{ number_format($feesDue) }}</span>
        </a>
        <a href="{{ route('fees.index') }}" wire:navigate class="card stat">
            <span class="stat-label">Unpaid invoices</span>
            <span class="stat-value">{{ $feesDueCount }}</span>
        </a>
    </section>

    <section>
        <h2 class="section-title">Today's classes</h2>

        @forelse ($todaysBatches as $batch)
            @php($slot = $batch->schedules->first())
            <div class="card mb-3 flex items-center gap-4 p-4">
                <div class="w-16 shrink-0 text-center">
                    <div class="text-lg font-semibold text-brand">{{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i') }}</div>
                    <div class="text-xs text-muted">{{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('A') }}</div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-medium text-ink">{{ $batch->name }}</div>
                    <div class="truncate text-sm text-muted">{{ $batch->course->name }} · {{ $batch->students_count }} students · {{ ucfirst($batch->mode) }}</div>
                </div>
            </div>
        @empty
            <div class="card p-6 text-center text-muted">
                No classes scheduled today.
            </div>
        @endforelse
    </section>
</div>
