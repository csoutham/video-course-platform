@props([
'maxWidth' => 'max-w-none',
'containerPadding' => 'px-4 py-6 lg:px-8',
'metaTitle' => null,
'metaDescription' => null,
])

@php
    $brandingName = $branding?->platformName ?? config('app.name', 'VideoCourses');
    $pageTitle = $metaTitle ?? $title ?? 'Admin';
    $fullTitle = trim($pageTitle.' · '.$brandingName);
    $pageDescription = $metaDescription ?? 'Operational admin interface for courses, orders, users, and platform settings.';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ $fullTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}" />

        @vite(['resources/app.css', 'resources/app.js'])
        @stack('head')
    </head>
    <body class="va-shell min-h-screen antialiased" data-admin-shell="true">
        <header class="va-topbar fixed top-0 right-0 left-0 z-50 border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-none items-center justify-between px-4 py-3 lg:px-6">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 lg:hidden"
                        data-admin-nav-toggle
                        aria-label="Open admin menu"
                        aria-controls="admin-nav-drawer"
                        aria-expanded="false">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        @if ($branding?->logoUrl)
                            <img
                                src="{{ $branding->logoUrl }}"
                                alt="{{ $brandingName }} logo"
                                class="h-7 w-auto max-w-[150px] object-contain" />
                        @endif

                        <span>{{ $brandingName }} Admin</span>
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('courses.index') }}" class="va-nav-chip">View Site</a>
                    <a href="{{ route('profile') }}" class="va-nav-chip">Profile</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="va-nav-chip">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-none pt-[57px]">
            <aside
                class="va-sidebar fixed top-[57px] bottom-0 left-0 hidden w-72 overflow-y-auto border-r border-slate-200 bg-white lg:block">
                @include('partials.admin.navigation')
            </aside>

            <main class="{{ $maxWidth }} {{ $containerPadding }} min-h-[calc(100vh-57px)] w-full lg:pl-76">
                {{ $slot }}
            </main>
        </div>

        <div id="admin-nav-drawer" class="pointer-events-none fixed inset-0 z-[60] lg:hidden" aria-hidden="true">
            <div
                data-admin-nav-backdrop
                class="absolute inset-0 bg-slate-900/40 opacity-0 transition-opacity duration-200"></div>
            <aside
                data-admin-nav-panel
                class="absolute top-0 left-0 h-full w-[82vw] max-w-xs -translate-x-full border-r border-slate-200 bg-white shadow-xl transition-transform duration-200">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <p class="text-sm font-semibold tracking-[0.16em] text-slate-500 uppercase">Admin Menu</p>
                    <button
                        type="button"
                        data-admin-nav-close
                        class="rounded-md p-2 text-slate-500 hover:bg-slate-100"
                        aria-label="Close admin menu">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @include('partials.admin.navigation')
            </aside>
        </div>

        @include('partials.admin-toast')

        <script>
            (() => {
                const drawer = document.getElementById('admin-nav-drawer');
                if (!drawer) return;

                const panel = drawer.querySelector('[data-admin-nav-panel]');
                const backdrop = drawer.querySelector('[data-admin-nav-backdrop]');
                const openTrigger = document.querySelector('[data-admin-nav-toggle]');
                const closeTrigger = drawer.querySelector('[data-admin-nav-close]');

                const open = () => {
                    drawer.classList.remove('pointer-events-none');
                    drawer.setAttribute('aria-hidden', 'false');
                    openTrigger?.setAttribute('aria-expanded', 'true');
                    panel?.classList.remove('-translate-x-full');
                    backdrop?.classList.remove('opacity-0');
                    document.body.classList.add('overflow-hidden');
                };

                const close = () => {
                    panel?.classList.add('-translate-x-full');
                    backdrop?.classList.add('opacity-0');
                    drawer.setAttribute('aria-hidden', 'true');
                    openTrigger?.setAttribute('aria-expanded', 'false');
                    window.setTimeout(() => drawer.classList.add('pointer-events-none'), 200);
                    document.body.classList.remove('overflow-hidden');
                };

                openTrigger?.addEventListener('click', open);
                closeTrigger?.addEventListener('click', close);
                backdrop?.addEventListener('click', close);
                drawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') close();
                });
            })();
        </script>
    </body>
</html>
