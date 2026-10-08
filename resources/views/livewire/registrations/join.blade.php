@php($school = \App\Models\Setting::get('school_name'))
@php($schoolPhone = \App\Models\Setting::get('school_phone'))

<div class="w-full max-w-xl">
    <div class="mb-6 text-center">
        <img src="/icons/icon-192.png" alt="" class="mx-auto mb-4 h-16 w-16 rounded-2xl shadow-sm">
        <h1 class="text-2xl font-semibold text-ink">{{ $school }}</h1>
        <p class="mt-1 text-muted">Student registration</p>
    </div>

    @if (! $open)
        <div class="card p-6 text-center text-ink">
            <p>Registrations are closed at the moment.</p>
            @if ($schoolPhone)
                <p class="mt-2 text-muted">Please call or WhatsApp <a href="tel:{{ $schoolPhone }}" class="text-brand underline">{{ $schoolPhone }}</a>.</p>
            @endif
        </div>
    @elseif ($sent)
        <div class="card space-y-4 p-6 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-success-soft text-2xl text-success" aria-hidden="true">✓</div>
            <h2 class="text-xl font-semibold text-ink">Thank you, we've got it!</h2>
            <p class="text-muted">We'll contact you on {{ $phone }} to confirm the batch and timings.</p>
            <button type="button" wire:click="another" class="btn-secondary">Register another child</button>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4">
            <section class="card grid gap-4 p-4 sm:grid-cols-2 sm:p-6">
                <h2 class="section-title sm:col-span-2">About your child</h2>

                <div class="sm:col-span-2">
                    <label for="child_name" class="label">Child's full name</label>
                    <input wire:model="child_name" id="child_name" type="text" autocomplete="off" class="input">
                    @error('child_name') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="dob" class="label">Date of birth</label>
                    <input wire:model="dob" id="dob" type="date" max="{{ now()->toDateString() }}" class="input">
                    @error('dob') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="grade" class="label">Grade / class <span class="text-muted">(optional)</span></label>
                    <input wire:model="grade" id="grade" type="text" placeholder="e.g. UKG, Grade 2" class="input">
                    @error('grade') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="school" class="label">School <span class="text-muted">(optional)</span></label>
                    <input wire:model="school" id="school" type="text" class="input">
                    @error('school') <p class="error">{{ $message }}</p> @enderror
                </div>

                @if ($courses->isNotEmpty())
                    <fieldset class="sm:col-span-2">
                        <legend class="label">Interested in</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($courses as $course)
                                <label wire:key="course-{{ $course->id }}" class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-line px-3 py-2 has-[:checked]:border-brand has-[:checked]:bg-brand-soft">
                                    <input type="checkbox" wire:model="course_ids" value="{{ $course->id }}" class="h-5 w-5 shrink-0 rounded accent-brand">
                                    <span class="text-ink">{{ $course->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div class="sm:col-span-2">
                    <label for="preferred_time" class="label">Preferred days or time <span class="text-muted">(optional)</span></label>
                    <input wire:model="preferred_time" id="preferred_time" type="text" placeholder="e.g. Weekday evenings, Saturday morning" class="input">
                    @error('preferred_time') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="notes" class="label">Anything we should know? <span class="text-muted">(optional)</span></label>
                    <textarea wire:model="notes" id="notes" rows="3" class="input" placeholder="Left-handed, finds some letters hard, allergies…"></textarea>
                    @error('notes') <p class="error">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="card grid gap-4 p-4 sm:grid-cols-2 sm:p-6">
                <h2 class="section-title sm:col-span-2">Parent / guardian</h2>

                <div class="sm:col-span-2">
                    <label for="guardian_name" class="label">Your name</label>
                    <input wire:model="guardian_name" id="guardian_name" type="text" autocomplete="name" class="input">
                    @error('guardian_name') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="label">Phone</label>
                    <input wire:model="phone" id="phone" type="tel" inputmode="tel" autocomplete="tel" class="input">
                    @error('phone') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="whatsapp" class="label">WhatsApp <span class="text-muted">(if different)</span></label>
                    <input wire:model="whatsapp" id="whatsapp" type="tel" inputmode="tel" class="input">
                    @error('whatsapp') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="email" class="label">Email <span class="text-muted">(optional)</span></label>
                    <input wire:model="email" id="email" type="email" autocomplete="email" class="input">
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="label">Address <span class="text-muted">(optional)</span></label>
                    <textarea wire:model="address" id="address" rows="2" autocomplete="street-address" class="input"></textarea>
                    @error('address') <p class="error">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-start gap-3 text-sm text-ink sm:col-span-2">
                    <input wire:model="photo_consent" type="checkbox" class="mt-0.5 h-5 w-5 shrink-0 rounded accent-brand">
                    <span>I'm happy for the teacher to photograph my child's worksheets to track handwriting progress. Photos stay private.</span>
                </label>

                {{-- Honeypot: hidden from people, filled in by spam bots. --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input wire:model="website" id="website" type="text" tabindex="-1" autocomplete="off">
                </div>
            </section>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Send registration</span>
                <span wire:loading wire:target="submit">Sending…</span>
            </button>
            <p class="text-center text-xs text-muted">Your details are only used by {{ $school }} to contact you about classes.</p>
        </form>
    @endif
</div>
