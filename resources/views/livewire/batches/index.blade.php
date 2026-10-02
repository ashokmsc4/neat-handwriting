@php($days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])

<div class="space-y-4">
    <h1 class="page-title">Batches</h1>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($batches as $batch)
            <div wire:key="batch-{{ $batch->id }}" class="card p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="truncate font-medium text-ink">{{ $batch->name }}</div>
                        <div class="truncate text-sm text-muted">{{ $batch->course->name }}@if ($batch->level) · {{ $batch->level->name }}@endif</div>
                    </div>
                    <span class="badge">{{ ucfirst($batch->mode) }}</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($batch->schedules as $slot)
                        <span class="chip">{{ $days[$slot->weekday] }} {{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i A') }}</span>
                    @endforeach
                </div>
                <div class="mt-3 text-sm text-muted">
                    {{ $batch->students_count }}@if ($batch->capacity) / {{ $batch->capacity }}@endif students
                </div>
            </div>
        @empty
            <div class="card p-8 text-center text-muted md:col-span-2 xl:col-span-3">
                No batches yet. Creating batches and schedules is the next step in the build.
            </div>
        @endforelse
    </div>
</div>
