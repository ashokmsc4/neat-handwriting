@php
    $tabs = ['overview' => 'Overview', 'progress' => 'Progress', 'samples' => 'Photos', 'fees' => 'Fees'];
    $currency = config('school.currency');
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

                <section class="card space-y-4 p-4 lg:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="section-title mb-0">Monthly check</h2>
                        @if (! $assessingLevelId)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($course->levels as $level)
                                    <button type="button" wire:click="startAssessment({{ $level->id }})" class="btn-secondary">Check “{{ $level->name }}”</button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($assessingLevelId && ($assessLevel = $course->levels->firstWhere('id', $assessingLevelId)))
                        <form wire:submit="saveAssessment" class="space-y-3">
                            <p class="text-sm text-muted">Score each skill for {{ $assessLevel->name }}: 1 = needs lots of help, 5 = excellent.</p>
                            @foreach ($assessLevel->skills as $skill)
                                <div wire:key="score-{{ $skill->id }}" class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <span class="text-ink">{{ $skill->name }}</span>
                                    <div class="segmented sm:w-64" role="radiogroup" aria-label="Score for {{ $skill->name }}">
                                        @for ($n = 1; $n <= 5; $n++)
                                            <label><input type="radio" wire:model="scores.{{ $skill->id }}" value="{{ $n }}"><span>{{ $n }}</span></label>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                            <div class="grid gap-3 sm:grid-cols-[1fr_12rem]">
                                <input wire:model="assessmentNote" type="text" placeholder="Overall note (optional)" class="input" aria-label="Overall note">
                                <input wire:model="assessmentDate" type="date" class="input" aria-label="Date">
                            </div>
                            @error('assessmentDate') <p class="error">{{ $message }}</p> @enderror
                            <div class="flex gap-2 sm:justify-end">
                                <button type="button" wire:click="$set('assessingLevelId', null)" class="btn-secondary flex-1 sm:flex-none">Cancel</button>
                                <button type="submit" class="btn-primary flex-1 sm:flex-none">Save check</button>
                            </div>
                        </form>
                    @endif

                    @forelse ($assessments as $a)
                        @php($avg = round($a->scores->avg('score'), 1))
                        <details wire:key="as-{{ $a->id }}" class="rounded-xl border border-line">
                            <summary class="flex cursor-pointer items-center justify-between gap-3 p-3">
                                <span>
                                    <span class="font-medium text-ink">{{ $a->date->format('j M Y') }}</span>
                                    <span class="text-sm text-muted">· {{ $a->level?->name }}</span>
                                </span>
                                <span class="rounded-full bg-brand-soft px-2.5 py-0.5 text-sm font-semibold text-brand">{{ $avg }}/5</span>
                            </summary>
                            <div class="space-y-1 border-t border-line p-3 text-sm">
                                @foreach ($a->scores as $score)
                                    <div class="flex justify-between gap-2"><span class="text-ink">{{ $score->skill?->name }}</span><span class="text-muted">{{ str_repeat('●', $score->score) }}{{ str_repeat('○', 5 - $score->score) }}</span></div>
                                @endforeach
                                @if ($a->overall_note) <p class="pt-2 text-muted">{{ $a->overall_note }}</p> @endif
                                <button type="button" wire:click="deleteAssessment({{ $a->id }})" wire:confirm="Delete this check?" class="pt-2 text-xs text-muted hover:text-danger">Delete</button>
                            </div>
                        </details>
                    @empty
                        @unless ($assessingLevelId)
                            <p class="text-sm text-muted">No checks yet. Do one each month to see improvement over time.</p>
                        @endunless
                    @endforelse
                </section>
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
    @if ($tab === 'fees')
        <div class="space-y-4">
            @php($owed = $invoices->sum(fn ($i) => $i->balance()))
            <section class="card flex items-center justify-between gap-3 p-4 lg:p-6">
                <div>
                    <div class="stat-label">Balance due</div>
                    <div @class(['stat-value', 'text-danger' => $owed > 0, 'text-success' => $owed <= 0])>{{ $currency }}{{ number_format($owed) }}</div>
                </div>
                <button type="button" wire:click="startCharge" class="btn-secondary">+ Add charge</button>
            </section>

            @if ($addingCharge)
                <form wire:submit="saveCharge" class="card grid gap-3 p-4 sm:grid-cols-2 lg:p-6">
                    <h2 class="section-title sm:col-span-2">New charge</h2>
                    <div class="sm:col-span-2">
                        <label for="chargePlanId" class="label">From fee plan <span class="text-muted">(optional)</span></label>
                        <select wire:model.live="chargePlanId" id="chargePlanId" class="input">
                            <option value="">Custom amount</option>
                            @foreach ($feePlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} · {{ $currency }}{{ number_format($plan->amount) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="chargeLabel" class="label">For</label>
                        <input wire:model="chargeLabel" id="chargeLabel" type="text" placeholder="e.g. Oct 2026, Workbook" class="input">
                        @error('chargeLabel') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="chargeDue" class="label">Due on</label>
                        <input wire:model="chargeDue" id="chargeDue" type="date" class="input">
                        @error('chargeDue') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="chargeAmount" class="label">Amount ({{ $currency }})</label>
                        <input wire:model="chargeAmount" id="chargeAmount" type="number" inputmode="decimal" step="0.01" class="input">
                        @error('chargeAmount') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="chargeDiscount" class="label">Discount <span class="text-muted">(optional)</span></label>
                        <input wire:model="chargeDiscount" id="chargeDiscount" type="number" inputmode="decimal" step="0.01" placeholder="e.g. sibling discount" class="input">
                        @error('chargeDiscount') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2 sm:col-span-2 sm:justify-end">
                        <button type="button" wire:click="$set('addingCharge', false)" class="btn-secondary flex-1 sm:flex-none">Cancel</button>
                        <button type="submit" class="btn-primary flex-1 sm:flex-none">Add charge</button>
                    </div>
                </form>
            @endif

            @if ($enrollments->isNotEmpty())
                <section class="card p-4 lg:p-6">
                    <h2 class="section-title">Fee plan per batch</h2>
                    <div class="space-y-3">
                        @foreach ($enrollments as $enrollment)
                            <div wire:key="enr-{{ $enrollment->id }}" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <span class="text-ink">{{ $enrollment->batch->name }}</span>
                                <select class="input sm:w-72" aria-label="Fee plan for {{ $enrollment->batch->name }}"
                                        wire:change="setEnrollmentPlan({{ $enrollment->id }}, $event.target.value)">
                                    <option value="">No fee plan</option>
                                    @foreach ($feePlans as $plan)
                                        <option value="{{ $plan->id }}" @selected($enrollment->fee_plan_id === $plan->id)>{{ $plan->name }} · {{ $currency }}{{ number_format($plan->amount) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-muted">Monthly plans are billed automatically each month.</p>
                </section>
            @endif

            <section>
                <h2 class="section-title">Invoices</h2>
                <div class="card divide-y divide-line">
                    @forelse ($invoices as $invoice)
                        @php($balance = $invoice->balance())
                        <div wire:key="inv-{{ $invoice->id }}" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium text-ink">{{ $invoice->period_label }}</div>
                                    <div class="text-sm text-muted">
                                        {{ $currency }}{{ number_format($invoice->amount) }}@if ((float) $invoice->discount > 0) − {{ $currency }}{{ number_format($invoice->discount) }} discount @endif
                                        · due {{ $invoice->due_date->format('j M Y') }}
                                    </div>
                                </div>
                                <span @class([
                                    'shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-success-soft text-success' => $invoice->status->value === 'paid',
                                    'bg-warning-soft text-warning' => $invoice->status->value === 'partial',
                                    'bg-danger-soft text-danger' => $invoice->status->value === 'due',
                                    'bg-surface-2 text-muted' => $invoice->status->value === 'waived',
                                ])>{{ $invoice->status->label() }}</span>
                            </div>
                            @if ($invoice->payments->isNotEmpty())
                                <ul class="mt-2 space-y-1 text-sm">
                                    @foreach ($invoice->payments as $payment)
                                        <li class="flex justify-between gap-2 text-muted">
                                            <span>{{ $payment->paid_on->format('j M') }} · {{ $payment->method->label() }} · {{ $currency }}{{ number_format($payment->amount) }}</span>
                                            <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="text-brand">{{ $payment->receipt_no }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($balance > 0)
                                <div class="mt-3 flex gap-2">
                                    <button type="button" wire:click="startPayment({{ $invoice->id }})" class="btn-primary flex-1 sm:flex-none">Mark paid · {{ $currency }}{{ number_format($balance) }}</button>
                                    <button type="button" wire:click="waive({{ $invoice->id }})" wire:confirm="Waive this charge? It will no longer show as due." class="btn-secondary">Waive</button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-muted">No invoices yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    @include('partials.payment-sheet')
</div>
