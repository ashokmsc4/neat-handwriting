@php
    $tabs = ['overview' => 'Overview', 'progress' => 'Progress', 'samples' => 'Photos'];
    $chip = [
        'not_started' => 'bg-surface-2 text-muted',
        'practising' => 'bg-warning-soft text-warning',
        'mastered' => 'bg-success-soft text-success',
    ];
@endphp

<div class="space-y-4">
    <header class="flex items-center gap-3">
        <a href="{{ route('students.index') }}" wire:navigate class="btn-ghost" aria-label="Back to students">←</a>
        <div class="avatar hidden h-12 w-12 text-lg sm:flex">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</div>
        <div class="min-w-0 flex-1">
            <h1 class="page-title truncate">{{ $student->name }}</h1>
            <p class="truncate text-sm text-muted">
                {{ collect([$student->grade, $student->school])->filter()->join(' · ') ?: 'No grade added' }}
                @if ($student->status->value !== 'active') · <span class="text-warning">{{ $student->status->label() }}</span> @endif
            </p>
        </div>
        <a href="{{ route('students.edit', $student) }}" wire:navigate class="btn-secondary shrink-0">Edit</a>
    </header>

    <nav class="segmented" aria-label="Student sections">
        @foreach ($tabs as $key => $label)
            <label><input type="radio" wire:model.live="tab" value="{{ $key }}"><span>{{ $label }}</span></label>
        @endforeach
    </nav>

    @if ($tab === 'overview')
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="card p-4 lg:p-6">
                <h2 class="section-title">Attendance, last 30 days</h2>
                @if ($sessionCount)
                    <div class="flex items-baseline gap-2">
                        <span class="stat-value">{{ round($attendedCount / $sessionCount * 100) }}%</span>
                        <span class="text-sm text-muted">{{ $attendedCount }} of {{ $sessionCount }} classes</span>
                    </div>
                    <ul class="mt-3 divide-y divide-line text-sm">
                        @foreach ($recent as $mark)
                            <li class="flex items-center justify-between gap-2 py-2">
                                <span class="text-ink">{{ $mark->classSession->date->format('D j M') }} <span class="text-muted">· {{ $mark->classSession->batch->name }}</span></span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-success-soft text-success' => $mark->status->value === 'present',
                                    'bg-danger-soft text-danger' => $mark->status->value === 'absent',
                                    'bg-warning-soft text-warning' => $mark->status->value === 'late',
                                    'bg-surface-2 text-muted' => $mark->status->value === 'excused',
                                ])>{{ $mark->status->label() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-muted">No classes recorded yet.</p>
                @endif
            </section>

            <div class="space-y-4">
                <section class="card p-4 lg:p-6">
                    <h2 class="section-title">Batches</h2>
                    @forelse ($batches as $batch)
                        <a href="{{ route('batches.edit', $batch) }}" wire:navigate class="block py-1 text-ink hover:text-brand">
                            {{ $batch->name }} <span class="text-sm text-muted">· {{ $batch->course->name }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-muted">Not in any batch yet.</p>
                    @endforelse
                </section>

                <section class="card p-4 lg:p-6">
                    <h2 class="section-title">Parent</h2>
                    @if ($guardian)
                        <p class="font-medium text-ink">{{ $guardian->name }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="tel:{{ $guardian->phone }}" class="btn-secondary">Call {{ $guardian->phone }}</a>
                            <a href="https://wa.me/{{ $guardian->whatsappNumber() }}" target="_blank" rel="noopener" class="btn-secondary">WhatsApp</a>
                        </div>
                    @else
                        <p class="text-sm text-muted">No parent details.</p>
                    @endif
                </section>

                @if ($student->notes)
                    <section class="card p-4 lg:p-6">
                        <h2 class="section-title">Notes</h2>
                        <p class="text-sm whitespace-pre-line text-ink">{{ $student->notes }}</p>
                    </section>
                @endif
            </div>
        </div>
    @endif

    @if ($tab === 'progress')
        <div class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <select wire:model.live="courseId" class="input sm:w-72" aria-label="Course">
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <p class="text-sm text-muted">Tap a skill to move it along: not started → practising → mastered.</p>
            </div>

            @if ($course)
                @foreach ($course->levels as $level)
                    @php
                        $mastered = $level->skills->filter(fn ($s) => $this->skillStatuses->get($s->id)?->status->value === 'mastered')->count();
                    @endphp
                    <section wire:key="level-{{ $level->id }}" class="card p-4 lg:p-6">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h2 class="font-semibold text-ink">{{ $level->name }}</h2>
                            <span class="text-sm text-muted">{{ $mastered }}/{{ $level->skills->count() }} mastered</span>
                        </div>
                        <div class="mb-4 h-2 overflow-hidden rounded-full bg-surface-2">
                            <div class="h-full rounded-full bg-success" style="width: {{ $level->skills->count() ? round($mastered / $level->skills->count() * 100) : 0 }}%"></div>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($level->skills as $skill)
                                @php($status = $this->skillStatuses->get($skill->id)?->status ?? \App\Enums\SkillStatus::NotStarted)
                                <button type="button" wire:click="cycleSkill({{ $skill->id }})" wire:key="skill-{{ $skill->id }}"
                                        class="flex min-h-12 items-center justify-between gap-3 rounded-xl border border-line px-3 py-2 text-left transition hover:border-brand">
                                    <span class="text-ink">{{ $skill->name }}</span>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $chip[$status->value] }}">{{ $status->label() }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @endif
        </div>
    @endif

    @if ($tab === 'samples')
        <div class="space-y-4">
            <form wire:submit="savePhoto" class="card space-y-3 p-4 lg:p-6"
                  x-data="photoPicker($wire)" x-on:photo-saved.window="clear()">
                <h2 class="section-title">Add a handwriting photo</h2>

                <label class="flex min-h-28 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-line p-4 text-center hover:border-brand">
                    <template x-if="!preview">
                        <span class="text-muted">
                            <span class="block text-2xl">📷</span>
                            Take a photo or choose one
                        </span>
                    </template>
                    <template x-if="preview">
                        <img :src="preview" alt="Selected photo" class="max-h-64 rounded-lg">
                    </template>
                    <input type="file" accept="image/*" class="sr-only" x-ref="file" x-on:change="pick($event)">
                </label>
                <p x-show="uploading" class="text-sm text-muted">Uploading… <span x-text="progress + '%'"></span></p>
                @error('photo') <p class="error">{{ $message }}</p> @enderror

                <div class="grid gap-3 sm:grid-cols-[1fr_12rem]">
                    <input wire:model="caption" type="text" placeholder="Caption, e.g. Letter formation a–e" class="input" aria-label="Caption">
                    <input wire:model="photoDate" type="date" class="input" aria-label="Date">
                </div>
                @error('photoDate') <p class="error">{{ $message }}</p> @enderror

                <button type="submit" class="btn-primary w-full sm:w-auto" x-bind:disabled="uploading || !preview" wire:loading.attr="disabled" wire:target="savePhoto">
                    <span wire:loading.remove wire:target="savePhoto">Save photo</span>
                    <span wire:loading wire:target="savePhoto">Saving…</span>
                </button>
            </form>

            @if ($samples->count() >= 2)
                @php($first = $samples->last())
                @php($latest = $samples->first())
                <section class="card p-4 lg:p-6">
                    <h2 class="section-title">Before and after</h2>
                    <div class="grid max-w-xl grid-cols-2 gap-3">
                        @foreach ([['First', $first], ['Latest', $latest]] as [$label, $s])
                            <figure>
                                <a href="{{ $s->url() }}" target="_blank"><img src="{{ $s->url(true) }}" alt="{{ $label }} sample" class="aspect-[3/4] w-full rounded-xl bg-surface-2 object-cover"></a>
                                <figcaption class="mt-1 text-sm text-muted">{{ $label }} · {{ $s->date->format('j M Y') }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            <section>
                <h2 class="section-title">All photos</h2>
                @if ($samples->isEmpty())
                    <div class="card p-8 text-center text-muted">No handwriting photos yet. Add one to start tracking improvement.</div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($samples as $s)
                            <figure wire:key="sample-{{ $s->id }}" class="card overflow-hidden">
                                <a href="{{ $s->url() }}" target="_blank"><img src="{{ $s->url(true) }}" alt="{{ $s->caption ?? 'Handwriting sample' }}" loading="lazy" class="aspect-[3/4] w-full bg-surface-2 object-cover"></a>
                                <figcaption class="flex items-start justify-between gap-2 p-2 text-sm">
                                    <span class="min-w-0">
                                        <span class="block text-ink">{{ $s->date->format('j M Y') }}</span>
                                        @if ($s->caption) <span class="block truncate text-muted">{{ $s->caption }}</span> @endif
                                    </span>
                                    <button type="button" wire:click="deletePhoto({{ $s->id }})" wire:confirm="Delete this photo?" class="text-muted hover:text-danger" aria-label="Delete photo">✕</button>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    @endif
</div>
