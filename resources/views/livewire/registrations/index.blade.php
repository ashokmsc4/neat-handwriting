<div class="space-y-6">
    <x-back-header :href="route('students.index')" title="Registrations" />

    <section class="card space-y-3 p-4 lg:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="section-title mb-0">Registration link for parents</h2>
            <span class="badge {{ $open ? '' : 'bg-surface-2 text-muted' }}">{{ $open ? 'Open' : 'Closed' }}</span>
        </div>

        <div x-data="{ copied: false }" class="flex gap-2">
            <input type="text" readonly value="{{ $link }}" class="input min-w-0 flex-1 text-sm" x-on:focus="$el.select()" aria-label="Registration link">
            <button type="button" class="btn-secondary shrink-0"
                x-on:click="navigator.clipboard.writeText(@js($link)); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-text="copied ? 'Copied ✓' : 'Copy'">Copy</span>
            </button>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener" class="btn-primary">Share on WhatsApp</a>
            <a href="{{ $link }}" target="_blank" rel="noopener" class="btn-secondary">Preview form</a>
            <button type="button" wire:click="toggleOpen" class="btn-secondary">{{ $open ? 'Close registrations' : 'Open registrations' }}</button>
            <button type="button" wire:click="newLink" wire:confirm="Make a new link? The old link will stop working." class="btn-secondary">New link</button>
        </div>
        <p class="text-xs text-muted">Anyone with this link can send a registration. New ones appear below for you to accept; nothing is added to your students until you do.</p>
    </section>

    <section>
        <h2 class="section-title">New ({{ $pending->count() }})</h2>

        <div class="grid gap-3 lg:grid-cols-2">
            @forelse ($pending as $r)
                <article wire:key="reg-{{ $r->id }}" class="card space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-lg font-semibold text-ink">{{ $r->child_name }}</h3>
                            <p class="text-sm text-muted">
                                @if ($r->dob) {{ $r->dob->age }} yrs ({{ $r->dob->format('j M Y') }}) @endif
                                @if ($r->grade) · {{ $r->grade }} @endif
                                @if ($r->school) · {{ $r->school }} @endif
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-muted">{{ $r->created_at->diffForHumans() }}</span>
                    </div>

                    @if ($r->course_ids)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($r->course_ids as $courseId)
                                @if (isset($courseNames[$courseId])) <span class="chip">{{ $courseNames[$courseId] }}</span> @endif
                            @endforeach
                        </div>
                    @endif

                    <dl class="grid gap-1 text-sm">
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-muted">Parent</dt><dd class="text-ink">{{ $r->guardian_name }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-muted">Phone</dt><dd><a href="tel:{{ $r->phone }}" class="text-brand">{{ $r->phone }}</a>@if ($r->whatsapp) · WhatsApp {{ $r->whatsapp }}@endif</dd></div>
                        @if ($r->email) <div class="flex gap-2"><dt class="w-24 shrink-0 text-muted">Email</dt><dd class="truncate text-ink">{{ $r->email }}</dd></div> @endif
                        @if ($r->preferred_time) <div class="flex gap-2"><dt class="w-24 shrink-0 text-muted">Prefers</dt><dd class="text-ink">{{ $r->preferred_time }}</dd></div> @endif
                        @if ($r->notes) <div class="flex gap-2"><dt class="w-24 shrink-0 text-muted">Notes</dt><dd class="whitespace-pre-line text-ink">{{ $r->notes }}</dd></div> @endif
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-muted">Photos</dt><dd class="text-ink">{{ $r->photo_consent ? 'OK to photograph worksheets' : 'No consent given' }}</dd></div>
                    </dl>

                    <div class="flex gap-2">
                        <button type="button" wire:click="accept({{ $r->id }})" class="btn-primary flex-1">Add as student</button>
                        <button type="button" wire:click="decline({{ $r->id }})" wire:confirm="Decline {{ $r->child_name }}'s registration?" class="btn-secondary">Decline</button>
                    </div>
                </article>
            @empty
                <div class="card p-6 text-center text-muted lg:col-span-2">No new registrations. Share the link above with parents.</div>
            @endforelse
        </div>
    </section>

    @if ($handled->isNotEmpty())
        <section>
            <h2 class="section-title">Earlier</h2>
            <div class="card divide-y divide-line">
                @foreach ($handled as $r)
                    <div wire:key="done-{{ $r->id }}" class="flex items-center gap-3 p-4">
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium text-ink">{{ $r->child_name }}</div>
                            <div class="truncate text-sm text-muted">{{ $r->guardian_name }} · {{ $r->created_at->format('j M') }}</div>
                        </div>
                        @if ($r->status === 'accepted' && $r->student)
                            <a href="{{ route('students.show', $r->student) }}" wire:navigate class="badge">Added</a>
                        @elseif ($r->status === 'accepted')
                            <span class="badge">Added</span>
                        @else
                            <button type="button" wire:click="restore({{ $r->id }})" class="text-sm text-brand">Declined · undo</button>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
