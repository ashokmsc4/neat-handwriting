<div class="space-y-4">
    <header class="flex items-center gap-3">
        <a href="{{ $student ? route('students.show', $student) : route('students.index') }}" wire:navigate class="btn-ghost" aria-label="Back">←</a>
        <h1 class="page-title">{{ $student ? $student->name : 'New student' }}</h1>
    </header>

    <form wire:submit="save" class="space-y-4 pb-24 lg:pb-0">
        <section class="card grid gap-4 p-4 sm:grid-cols-2 lg:p-6">
            <h2 class="section-title sm:col-span-2">Child</h2>

            <div class="sm:col-span-2">
                <label for="name" class="label">Full name</label>
                <input wire:model="name" id="name" type="text" class="input" autocomplete="off">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="dob" class="label">Date of birth</label>
                <input wire:model="dob" id="dob" type="date" class="input">
                @error('dob') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="grade" class="label">Grade / class</label>
                <input wire:model="grade" id="grade" type="text" placeholder="e.g. UKG, Grade 2" class="input">
                @error('grade') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="school" class="label">School</label>
                <input wire:model="school" id="school" type="text" class="input">
                @error('school') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="joined_on" class="label">Joined on</label>
                <input wire:model="joined_on" id="joined_on" type="date" class="input">
                @error('joined_on') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="status" class="label">Status</label>
                <select wire:model="status" id="status" class="input">
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="label">Notes</label>
                <textarea wire:model="notes" id="notes" rows="3" class="input" placeholder="Left-handed, needs pencil grip support…"></textarea>
                @error('notes') <p class="error">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="card grid gap-4 p-4 sm:grid-cols-2 lg:p-6">
            <h2 class="section-title sm:col-span-2">Parent</h2>

            <div class="sm:col-span-2">
                <label for="guardian_name" class="label">Name</label>
                <input wire:model="guardian_name" id="guardian_name" type="text" class="input">
                @error('guardian_name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="guardian_phone" class="label">Phone</label>
                <input wire:model="guardian_phone" id="guardian_phone" type="tel" inputmode="tel" class="input">
                @error('guardian_phone') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="guardian_whatsapp" class="label">WhatsApp <span class="text-muted">(if different)</span></label>
                <input wire:model="guardian_whatsapp" id="guardian_whatsapp" type="tel" inputmode="tel" class="input">
                @error('guardian_whatsapp') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="guardian_email" class="label">Email</label>
                <input wire:model="guardian_email" id="guardian_email" type="email" class="input">
                @error('guardian_email') <p class="error">{{ $message }}</p> @enderror
            </div>
        </section>

        <div class="sticky-actions">
            <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save student</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
