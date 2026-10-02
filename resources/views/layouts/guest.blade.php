<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="flex min-h-dvh items-center justify-center bg-surface-2 px-4 py-10 font-sans antialiased">
    {{ $slot }}
</body>
</html>
