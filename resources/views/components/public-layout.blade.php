@props([
'maxWidth' => 'max-w-6xl',
'containerPadding' => 'px-6 py-8',
'metaTitle' => null,
'metaDescription' => null,
'metaImage' => null,
'canonicalUrl' => null,
])

@php
    $brandingName = $branding?->platformName ?? config('app.name', 'VideoCourses');
    $pageTitle = $metaTitle ?? $title ?? $brandingName;
    $pageDescription =
        $metaDescription ??
        'Learn with practical video courses, instant checkout, and clear step-by-step progress.';
    $pageImage = $metaImage ?: ($branding?->logoUrl ?: asset('favicon.ico'));
    $pageUrl = $canonicalUrl ?: url()->current();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}" />
        <link rel="canonical" href="{{ $pageUrl }}" />

        <meta property="og:type" content="website" />
        <meta property="og:title" content="{{ $pageTitle }}" />
        <meta property="og:description" content="{{ $pageDescription }}" />
        <meta property="og:url" content="{{ $pageUrl }}" />
        <meta property="og:image" content="{{ $pageImage }}" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="{{ $pageTitle }}" />
        <meta name="twitter:description" content="{{ $pageDescription }}" />
        <meta name="twitter:image" content="{{ $pageImage }}" />

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
        @stack('head')
    </head>
    <body class="vc-shell flex min-h-screen flex-col text-slate-900 antialiased">
        @include('partials.navigation')

        <main class="{{ $maxWidth }} {{ $containerPadding }} mx-auto w-full flex-1">
            {{ $slot }}
        </main>

        @include('partials.footer')
    </body>
</html>
