@props([
'maxWidth' => 'max-w-6xl',
'containerPadding' => 'px-6 py-8',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ $title ?? config('app.name', 'VideoCourses') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/app.css', 'resources/app.js'])
    </head>
    <body class="vc-shell flex min-h-screen flex-col text-slate-900 antialiased">
        @include('partials.navigation')

        <main class="{{ $maxWidth }} {{ $containerPadding }} mx-auto w-full flex-1">
            {{ $slot }}
        </main>

        @include('partials.footer')
    </body>
</html>
