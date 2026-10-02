@php
    $nav = [
        ['route' => 'dashboard', 'label' => 'Today', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z'],
        ['route' => 'students.index', 'label' => 'Students', 'icon' => 'M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.28.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z'],
        ['route' => 'batches.index', 'label' => 'Batches', 'icon' => 'M4 6h16M4 12h16M4 18h7'],
        ['route' => 'fees.index', 'label' => 'Fees', 'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-dvh bg-surface-2 font-sans text-ink antialiased">
    {{-- Sidebar: iPad landscape and desktop --}}
    <aside class="fixed inset-y-0 left-0 hidden w-60 flex-col border-r border-line bg-surface lg:flex">
        <div class="flex items-center gap-3 px-5 py-5">
            <img src="/icons/icon-192.png" alt="" class="h-9 w-9 rounded-xl">
            <span class="font-semibold">{{ config('school.name') }}</span>
        </div>
        <nav class="flex-1 space-y-1 px-3">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" wire:navigate
                   @class(['nav-link', 'nav-link-active' => request()->routeIs(str_replace('.index', '.*', $item['route']))])>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-line p-3">
            @csrf
            <div class="px-3 pb-2 text-sm text-muted">{{ auth()->user()->name }}</div>
            <button type="submit" class="nav-link w-full">Sign out</button>
        </form>
    </aside>

    {{-- Top bar: phone and iPad portrait --}}
    <header class="sticky top-0 z-20 flex items-center justify-between border-b border-line bg-surface/90 px-4 pb-3 backdrop-blur lg:hidden" style="padding-top: max(0.75rem, env(safe-area-inset-top))">
        <div class="flex items-center gap-2">
            <img src="/icons/icon-192.png" alt="" class="h-7 w-7 rounded-lg">
            <span class="font-semibold">{{ config('school.name') }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-muted">Sign out</button>
        </form>
    </header>

    <div class="lg:pl-60">
    <main class="mx-auto max-w-5xl px-4 pt-4 pb-28 lg:px-8 lg:pt-8 lg:pb-10">
        @if (session('status'))
            <div class="mb-4 rounded-xl bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ session('status') }}</div>
        @endif

        {{ $slot }}
    </main>
    </div>

    {{-- Bottom tab bar: phone and iPad portrait --}}
    <nav class="fixed inset-x-0 bottom-0 z-20 grid grid-cols-4 border-t border-line bg-surface/95 backdrop-blur lg:hidden" style="padding-bottom: env(safe-area-inset-bottom)">
        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
               @class(['tab-link', 'tab-link-active' => request()->routeIs(str_replace('.index', '.*', $item['route']))])>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</body>
</html>
