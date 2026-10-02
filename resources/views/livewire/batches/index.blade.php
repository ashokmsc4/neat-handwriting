@php($days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])

<div class="space-y-4">
    <header class="flex items-center justify-between gap-3">
        <h1 class="page-title">Batches</h1>
        <a href="{{ route('batches.create') }}" wire:navigate class="btn-primary">+ Add batch</a>
    </header>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($batches as $batch)
            <a href="{{ route('batches.edit', $batch) }}" wire:navigate wire:key="batch-{{ $batch->id }}" class="card block p-4 transition hover:border-brand">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="truncate font-medium text-ink">{{ $batch->name }}</div>
                        <div class="truncate text-sm text-muted">{{ $batch->course->name }}@if ($batch->level) · {{ $batch->level->name }}@endif</div>
                    </div>
                    <span class="badge">{{ $batch->active ? ($batch->mode === 'online' ? 'Online' : 'In person') : 'Stopped' }}</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($batch->schedules as $slot)
                        <span class="chip">{{ $days[$slot->weekday] }} {{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i A') }}</span>
                    @endforeach
                </div>
                <div class="mt-3 text-sm text-muted">
                    {{ $batch->students_count }}@if ($batch->capacity) / {{ $batch->capacity }}@endif students
                </div>
            </a>
        @empty
            <div class="card p-8 text-center text-muted md:col-span-2 xl:col-span-3">
                No batches yet. Add one to set its weekly days and students.
            </div>
        @endforelse
    </div>
</div>
