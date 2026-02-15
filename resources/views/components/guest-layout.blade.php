<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/app.css', 'resources/app.js'])
    </head>
    <body class="flex min-h-screen flex-col font-sans text-gray-900 antialiased">
        <main class="flex flex-1 flex-col items-center justify-center bg-gray-100 px-4 py-6 sm:py-0">
            <div>
                <a href="{{ route('courses.index') }}" class="text-lg font-semibold tracking-tight text-slate-900">
                    {{ config('app.name', 'VideoCourses') }}
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {{ $slot }}
            </div>
        </main>

        @include('partials.footer')
    </body>
</html>
