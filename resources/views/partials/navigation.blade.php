<header class="border-b border-slate-200/80 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="{{ route('courses.index') }}" class="text-lg font-semibold tracking-tight text-slate-900">
            {{ config('app.name', 'VideoCourses') }}
        </a>
        <nav class="flex items-center gap-2 text-sm font-medium text-slate-600">
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
                    Login
                </a>
            @endauth
        </nav>
    </div>
</header>
