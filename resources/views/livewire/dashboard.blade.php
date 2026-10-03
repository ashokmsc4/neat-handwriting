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
        <a href="{{ route('reports.index') }}" wire:navigate class="card stat">
            <span class="stat-label">Attendance in {{ $today->format('M') }}</span>
            <span class="stat-value">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</span>
        </a>
        <a href="{{ route('fees.index') }}" wire:navigate class="card stat">
            <span class="stat-label">Fees outstanding</span>
            <span @class(['stat-value', 'text-danger' => $feesDue > 0])>{{ $currency }}{{ number_format($feesDue) }}</span>
        </a>
        <a href="{{ route('fees.index', ['tab' => 'paid']) }}" wire:navigate class="card stat">
            <span class="stat-label">Collected in {{ $today->format('M') }}</span>
            <span class="stat-value text-success">{{ $currency }}{{ number_format($collected) }}</span>
        </a>
    </section>

    <section>
        <h2 class="section-title">Today's classes</h2>

        @forelse ($todaysBatches as $batch)
            @php($slot = $batch->schedules->first())
            <a href="{{ route('attendance.take', $batch) }}" wire:navigate wire:key="today-{{ $batch->id }}" class="card mb-3 flex items-center gap-4 p-4 transition hover:border-brand">
                <div class="w-16 shrink-0 text-center">
                    <div class="text-lg font-semibold text-brand">{{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i') }}</div>
                    <div class="text-xs text-muted">{{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('A') }}</div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-medium text-ink">{{ $batch->name }}</div>
                    <div class="truncate text-sm text-muted">{{ $batch->course->name }} · {{ $batch->students_count }} students · {{ $batch->mode === 'online' ? 'Online' : 'In person' }}</div>
                </div>
                @if ($batch->sessions->isNotEmpty())
                    <span class="rounded-full bg-success-soft px-2.5 py-1 text-xs font-medium text-success">Done ✓</span>
                @else
                    <span class="badge">Take attendance</span>
                @endif
            </a>
        @empty
            <div class="card p-6 text-center text-muted">
                No classes scheduled today.
            </div>
        @endforelse
    </section>

    @if ($birthdays->isNotEmpty())
        <section>
            <h2 class="section-title">Birthdays this week 🎂</h2>
            <div class="card divide-y divide-line">
                @foreach ($birthdays as $student)
                    <a href="{{ route('students.show', $student) }}" wire:navigate class="flex items-center justify-between gap-3 p-4 hover:bg-surface-2">
                        <span class="font-medium text-ink">{{ $student->name }}</span>
                        <span class="text-sm text-muted">
                            {{ $student->dob->format('m-d') === $today->format('m-d') ? 'Today!' : $student->dob->format('j M') }}
                            · turns {{ $student->dob->copy()->year($today->year)->lt($today->copy()->startOfDay()) ? $today->year + 1 - $student->dob->year : $today->year - $student->dob->year }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
