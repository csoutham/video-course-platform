<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'VideoCourses') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="{{ route('courses.index') }}" class="text-lg font-semibold tracking-tight">{{ config('app.name', 'VideoCourses') }}</a>
                <nav class="flex items-center gap-4 text-sm font-medium text-slate-600">
                    <a href="{{ route('courses.index') }}" class="hover:text-slate-900">Courses</a>
                    @auth
                        <a href="{{ route('my-courses.index') }}" class="hover:text-slate-900">My Courses</a>
                        <a href="{{ route('dashboard') }}" class="hover:text-slate-900">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-slate-900">Login</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8">
            {{ $slot }}
        </main>
    </body>
</html>
