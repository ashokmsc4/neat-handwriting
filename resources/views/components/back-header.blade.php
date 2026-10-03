@props(['href', 'title'])

<header class="flex items-center gap-3">
    <a href="{{ $href }}" wire:navigate class="btn-ghost" aria-label="Back">←</a>
    <h1 class="page-title min-w-0 flex-1 truncate">{{ $title }}</h1>
    {{ $slot }}
</header>
