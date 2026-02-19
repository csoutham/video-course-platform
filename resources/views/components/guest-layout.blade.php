<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ $branding?->platformName ?? config('app.name', 'VideoCourses') }}</title>

        @if (isset($branding))
            @foreach ($branding->fontPreconnectUrls as $preconnectUrl)
                <link rel="preconnect" href="{{ $preconnectUrl }}" />
            @endforeach

            @if ($branding->fontStylesheetUrl)
                <link href="{{ $branding->fontStylesheetUrl }}" rel="stylesheet" />
            @endif
        @endif

        @if (config('branding.enabled', true) && isset($branding))
            <style>
                :root {
                    --vc-font-sans: {!! $branding->fontCssFamily !!};
                    --vc-logo-height: {{ $branding->logoHeightPx }}px;
                    @foreach ($branding->colors as $token => $value)
                        --{{ $token }}: {{ $value }};
                    @endforeach
                }
            </style>
        @endif

        @vite(['resources/app.css', 'resources/app.js'])
    </head>
    <body class="vc-shell flex min-h-screen flex-col font-sans text-slate-900 antialiased">
        <main class="flex flex-1 flex-col items-center justify-center px-4 py-8">
            <div>
                <a
                    href="{{ route('courses.index') }}"
                    class="flex items-center gap-2 text-xl font-semibold tracking-tight text-slate-900">
                    @if ($branding?->logoUrl)
                        <img
                            src="{{ $branding->logoUrl }}"
                            alt="{{ $branding->platformName }} logo"
                            class="vc-logo object-contain" />
                    @endif

                    <span>{{ $branding?->platformName ?? config('app.name', 'VideoCourses') }}</span>
                </a>
            </div>

            <div class="vc-panel mt-6 w-full overflow-hidden px-6 py-5 sm:max-w-lg">
                {{ $slot }}
            </div>
        </main>

        @include('partials.footer')
    </body>
</html>
