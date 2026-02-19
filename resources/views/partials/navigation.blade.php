<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
        <a href="{{ route('courses.index') }}" class="flex items-center gap-2 text-lg font-semibold tracking-tight text-slate-900">
            @if ($branding?->logoUrl)
                <img
                    src="{{ $branding->logoUrl }}"
                    alt="{{ $branding->platformName }} logo"
                    class="h-8 w-auto max-w-36 object-contain" />
            @endif
            <span>{{ $branding?->platformName ?? config('app.name', 'VideoCourses') }}</span>
        </a>

        <nav class="hidden items-center gap-2 text-sm font-medium text-slate-600 lg:flex">
            <a
                href="{{ route('courses.index') }}"
                class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                Courses
            </a>
            @auth
                <a
                    href="{{ route('my-courses.index') }}"
                    class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                    My Courses
                </a>
                <a
                    href="{{ route('gifts.index') }}"
                    class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                    My Gifts
                </a>
                <a
                    href="{{ route('receipts.index') }}"
                    class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                    Receipts
                </a>
                @can('access-admin')
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                        Admin
                    </a>
                    <a
                        href="{{ route('admin.branding.edit') }}"
                        class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                        Branding
                    </a>
                @endcan

                <a href="{{ route('profile') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                    Profile
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                        Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900">
                    Sign in
                </a>
            @endauth
        </nav>

        <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 lg:hidden"
            data-mobile-nav-toggle
            aria-label="Open menu"
            aria-controls="mobile-site-nav"
            aria-expanded="false">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</header>

<div id="mobile-site-nav" class="pointer-events-none fixed inset-0 z-50 lg:hidden" aria-hidden="true">
    <div
        data-mobile-nav-backdrop
        class="absolute inset-0 bg-slate-900/30 opacity-0 transition-opacity duration-200"></div>
    <aside
        data-mobile-nav-panel
        class="absolute top-0 right-0 h-full w-[85vw] max-w-sm translate-x-full border-l border-slate-200 bg-white p-5 shadow-xl transition-transform duration-200">
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm font-semibold tracking-[0.18em] text-slate-500 uppercase">Menu</p>
            <button
                type="button"
                data-mobile-nav-close
                class="rounded-md p-2 text-slate-500 hover:bg-slate-100"
                aria-label="Close menu">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <nav class="space-y-1 text-sm font-medium text-slate-700">
            <a href="{{ route('courses.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">Courses</a>
            @auth
                <a href="{{ route('my-courses.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">
                    My Courses
                </a>
                <a href="{{ route('gifts.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">My Gifts</a>
                <a href="{{ route('receipts.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">
                    Receipts
                </a>
                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">
                        Admin
                    </a>
                    <a href="{{ route('admin.branding.edit') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">
                        Branding
                    </a>
                @endcan

                <a href="{{ route('profile') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">Profile</a>
                <form method="POST" action="{{ route('logout') }}" class="pt-2">
                    @csrf
                    <button
                        type="submit"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">Sign in</a>
            @endauth
        </nav>
    </aside>
</div>

<script>
    (() => {
        const drawer = document.getElementById('mobile-site-nav');
        if (!drawer) return;

        const panel = drawer.querySelector('[data-mobile-nav-panel]');
        const backdrop = drawer.querySelector('[data-mobile-nav-backdrop]');
        const openTrigger = document.querySelector('[data-mobile-nav-toggle]');
        const closeTrigger = drawer.querySelector('[data-mobile-nav-close]');

        const open = () => {
            drawer.classList.remove('pointer-events-none');
            drawer.setAttribute('aria-hidden', 'false');
            openTrigger?.setAttribute('aria-expanded', 'true');
            panel?.classList.remove('translate-x-full');
            backdrop?.classList.remove('opacity-0');
            document.body.classList.add('overflow-hidden');
        };

        const close = () => {
            panel?.classList.add('translate-x-full');
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
