<div class="space-y-4">
    <header class="flex items-center justify-between gap-3">
        <h1 class="page-title">Students</h1>
        <div class="flex gap-2">
            <a href="{{ route('registrations.index') }}" wire:navigate class="btn-secondary">Registrations</a>
            <a href="{{ route('students.create') }}" wire:navigate class="btn-primary">+ Add student</a>
        </div>
    </header>

    @if ($newRegistrations)
        <a href="{{ route('registrations.index') }}" wire:navigate class="flex items-center justify-between gap-3 rounded-xl bg-brand-soft px-4 py-3 text-brand">
            <span class="font-medium">{{ $newRegistrations }} new {{ Str::plural('registration', $newRegistrations) }} from parents</span>
            <span aria-hidden="true">›</span>
        </a>
    @endif

    <div class="flex flex-col gap-2 sm:flex-row">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search by child, parent or phone" class="input flex-1">
        <select wire:model.live="status" class="input sm:w-40">
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
            <option value="all">All</option>
        </select>
    </div>

    <div class="card divide-y divide-line">
        @forelse ($students as $student)
            <a href="{{ route('students.show', $student) }}" wire:navigate wire:key="student-{{ $student->id }}" class="flex items-center gap-3 p-4 hover:bg-surface-2">
                <div class="avatar">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</div>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-medium text-ink">{{ $student->name }}</div>
                    <div class="truncate text-sm text-muted">
                        {{ $student->guardian?->name ?? 'No parent added' }}@if ($student->grade) · {{ $student->grade }}@endif
                    </div>
                </div>
                @if ($student->status->value !== 'active')
                    <span class="badge">{{ $student->status->label() }}</span>
                @endif
            </a>
        @empty
            <div class="p-8 text-center text-muted">
                @if ($search !== '')
                    No students match “{{ $search }}”.
                @else
                    No students yet. Add your first one.
                @endif
            </div>
        @endforelse
    </div>

    {{ $students->links() }}
</div>
