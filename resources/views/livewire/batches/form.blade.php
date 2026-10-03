<div class="space-y-4">
    <header class="flex items-center gap-3">
        <a href="{{ route('batches.index') }}" wire:navigate class="btn-ghost" aria-label="Back to batches">←</a>
        <h1 class="page-title min-w-0 flex-1 truncate">{{ $batch ? $batch->name : 'New batch' }}</h1>
        @if ($batch)
            <a href="{{ route('attendance.take', $batch) }}" wire:navigate class="btn-secondary shrink-0">Attendance</a>
        @endif
    </header>

    <form wire:submit="save" class="space-y-4 pb-24 lg:pb-0">
        <section class="card grid gap-4 p-4 sm:grid-cols-2 lg:p-6">
            <h2 class="section-title sm:col-span-2">Batch</h2>

            <div class="sm:col-span-2">
                <label for="name" class="label">Name</label>
                <input wire:model="name" id="name" type="text" placeholder="e.g. Little Writers, Mon/Wed 4 PM" class="input" autocomplete="off">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="course_id" class="label">Course</label>
                <select wire:model.live="course_id" id="course_id" class="input">
                    @foreach ($this->courses as $course)
                        <option value="{{ $course->id }}">{{ $course->name }}</option>
                    @endforeach
                </select>
                @error('course_id') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="level_id" class="label">Level</label>
                <select wire:model="level_id" id="level_id" class="input">
                    <option value="">Mixed / not set</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                    @endforeach
                </select>
                @error('level_id') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="fee_plan_id" class="label">Fee plan</label>
                <select wire:model="fee_plan_id" id="fee_plan_id" class="input">
                    <option value="">No fee plan</option>
                    @foreach ($feePlans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} · {{ config('school.currency') }}{{ number_format($plan->amount) }} {{ strtolower($plan->typeLabel()) }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-muted">Used for students added to this batch. Monthly plans are billed automatically.</p>
                @error('fee_plan_id') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="label">Mode</span>
                <div class="segmented">
                    <label><input type="radio" wire:model.live="mode" value="offline"><span>In person</span></label>
                    <label><input type="radio" wire:model.live="mode" value="online"><span>Online</span></label>
                </div>
            </div>

            <div>
                <label for="capacity" class="label">Max students <span class="text-muted">(optional)</span></label>
                <input wire:model="capacity" id="capacity" type="number" inputmode="numeric" min="1" class="input">
                @error('capacity') <p class="error">{{ $message }}</p> @enderror
            </div>

            @if ($mode === 'online')
                <div class="sm:col-span-2">
                    <label for="meeting_link" class="label">Meeting link</label>
                    <input wire:model="meeting_link" id="meeting_link" type="url" placeholder="https://meet.google.com/…" class="input">
                    @error('meeting_link') <p class="error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label for="start_date" class="label">Starts on</label>
                <input wire:model="start_date" id="start_date" type="date" class="input">
                @error('start_date') <p class="error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 self-end pb-3 text-sm text-ink">
                <input wire:model="active" type="checkbox" class="h-5 w-5 rounded accent-brand">
                Batch is running
            </label>
        </section>

        <section class="card space-y-3 p-4 lg:p-6">
            <h2 class="section-title">Weekly schedule</h2>

            @foreach ($schedules as $i => $slot)
                <div wire:key="slot-{{ $i }}" class="grid grid-cols-[1fr_auto] gap-2 sm:grid-cols-[1fr_10rem_10rem_auto] sm:items-start">
                    <select wire:model="schedules.{{ $i }}.weekday" class="input" aria-label="Day">
                        @foreach ($weekdays as $d => $dayName)
                            <option value="{{ $d }}">{{ $dayName }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="removeSlot({{ $i }})" class="btn-ghost sm:order-last" aria-label="Remove this day">✕</button>
                    <div class="col-span-2 grid grid-cols-2 gap-2 sm:col-span-2">
                        <input wire:model="schedules.{{ $i }}.start_time" type="time" class="input" aria-label="Start time">
                        <input wire:model="schedules.{{ $i }}.end_time" type="time" class="input" aria-label="End time">
                    </div>
                    @error("schedules.$i.start_time") <p class="error col-span-full">{{ $message }}</p> @enderror
                    @error("schedules.$i.end_time") <p class="error col-span-full">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <button type="button" wire:click="addSlot" class="btn-secondary">+ Add a day</button>
        </section>

        <section class="card space-y-3 p-4 lg:p-6">
            <div class="flex items-center justify-between">
                <h2 class="section-title mb-0">Students</h2>
                <span class="text-sm text-muted">
                    {{ count($student_ids) }} selected @if ($capacity) of {{ $capacity }} @endif
                </span>
            </div>
            @if ($capacity && count($student_ids) > $capacity)
                <p class="text-sm text-danger">More students than the batch's maximum.</p>
            @endif

            <input wire:model.live.debounce.300ms="studentSearch" type="search" placeholder="Search students" class="input">

            <div class="max-h-80 divide-y divide-line overflow-y-auto rounded-xl border border-line">
                @forelse ($this->students as $student)
                    <label wire:key="pick-{{ $student->id }}" class="flex min-h-12 cursor-pointer items-center gap-3 px-3 py-2 hover:bg-surface-2">
                        <input type="checkbox" wire:model.live="student_ids" value="{{ $student->id }}" class="h-5 w-5 rounded accent-brand">
                        <span class="flex-1 truncate text-ink">{{ $student->name }}</span>
                        @if ($student->grade) <span class="text-sm text-muted">{{ $student->grade }}</span> @endif
                    </label>
                @empty
                    <p class="p-4 text-center text-sm text-muted">No students found. Add students first.</p>
                @endforelse
            </div>
        </section>

        <div class="sticky-actions">
            <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save batch</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
