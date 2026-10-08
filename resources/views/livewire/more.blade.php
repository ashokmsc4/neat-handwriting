@php
    $groups = [
        'Parents' => [
            ['registrations.index', 'Registration link', 'Share a form for parents to register their child'],
        ],
        'Reports' => [
            ['reports.index', 'Reports & exports', 'Attendance, collections and CSV downloads'],
        ],
        'Settings' => [
            ['settings.school', 'Class details', 'Name, phone and address on receipts; fee due day'],
            ['settings.curriculum', 'Courses & skills', 'Levels and the skill checklist for each course'],
            ['settings.fee-plans', 'Fee plans', 'Monthly fees, class packs and one-time charges'],
            ['settings.account', 'My account', 'Name, email and password'],
            ['settings.backups', 'Backups', 'Download a copy of all your data'],
        ],
    ];
@endphp

<div class="space-y-6">
    <h1 class="page-title">More</h1>

    @foreach ($groups as $title => $links)
        <section>
            <h2 class="section-title">{{ $title }}</h2>
            <div class="card divide-y divide-line">
                @foreach ($links as [$route, $label, $hint])
                    <a href="{{ route($route) }}" wire:navigate class="flex items-center gap-3 p-4 hover:bg-surface-2">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-ink">{{ $label }}</div>
                            <div class="truncate text-sm text-muted">{{ $hint }}</div>
                        </div>
                        <span class="text-muted" aria-hidden="true">›</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
        @csrf
        <button type="submit" class="btn-secondary w-full">Sign out</button>
    </form>
</div>
