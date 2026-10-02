@php($short = ['present' => 'P', 'absent' => 'A', 'late' => 'L', 'excused' => 'E'])

<div class="space-y-4">
    <header class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" wire:navigate class="btn-ghost" aria-label="Back to today">←</a>
        <div class="min-w-0">
            <h1 class="page-title truncate">{{ $batch->name }}</h1>
            <p class="text-sm text-muted">
                {{ $day->isToday() ? 'Today' : $day->format('l') }}, {{ $day->format('j M') }}
                @if ($startTime) · {{ \Illuminate\Support\Carbon::parse($startTime)->format('g:i A') }} @endif
                @if ($alreadySaved) · <span class="text-success">saved, editing</span> @endif
            </p>
        </div>
    </header>

    @if ($batch->mode === 'online' && $batch->meeting_link)
        <a href="{{ $batch->meeting_link }}" target="_blank" rel="noopener" class="btn-secondary w-full sm:w-auto">Open meeting link</a>
    @endif

    <form wire:submit="save" class="space-y-4 pb-24 lg:pb-0">
        @if ($students->isEmpty())
            <div class="card p-8 text-center text-muted">
                No students in this batch yet.
                <a href="{{ route('batches.edit', $batch) }}" wire:navigate class="text-brand underline">Add students</a>
            </div>
        @else
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted">
                    {{ collect($marks)->filter(fn ($s) => $s === 'present' || $s === 'late')->count() }} of {{ count($marks) }} here
                </span>
                <button type="button" wire:click="markAll('present')" class="font-medium text-brand">Mark all present</button>
            </div>

            <div class="card divide-y divide-line">
                @foreach ($students as $student)
                    <div wire:key="mark-{{ $student->id }}" class="flex items-center gap-3 p-3 sm:p-4">
                        <div class="avatar hidden sm:flex">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</div>
                        <div class="line-clamp-2 min-w-0 flex-1 leading-snug font-medium break-words text-ink">{{ $student->name }}</div>
                        <div class="segmented attendance shrink-0" role="radiogroup" aria-label="Attendance for {{ $student->name }}">
                            @foreach ($statuses as $status)
                                <label title="{{ $status->label() }}">
                                    <input type="radio" wire:model.live="marks.{{ $student->id }}" value="{{ $status->value }}">
                                    <span data-status="{{ $status->value }}">
                                        <span class="sm:hidden">{{ $short[$status->value] }}</span>
                                        <span class="hidden sm:inline">{{ $status->label() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card p-4">
                <label for="topicNote" class="label">What did you work on? <span class="text-muted">(optional)</span></label>
                <textarea wire:model="topicNote" id="topicNote" rows="2" class="input" placeholder="Letters a, c, d · sounds s a t"></textarea>
                @error('topicNote') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="sticky-actions">
                <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save attendance</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        @endif
    </form>
</div>
