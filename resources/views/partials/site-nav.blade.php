<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="{{ route('courses.index') }}" class="text-lg font-semibold tracking-tight">{{ config('app.name', 'VideoCourses') }}</a>
        <nav class="flex items-center gap-4 text-sm font-medium text-slate-600">
            <a href="{{ route('courses.index') }}" class="hover:text-slate-900">Courses</a>
            @auth
                <a href="{{ route('my-courses.index') }}" class="hover:text-slate-900">My Courses</a>
                <a href="{{ route('gifts.index') }}" class="hover:text-slate-900">My Gifts</a>
                <a href="{{ route('receipts.index') }}" class="hover:text-slate-900">Receipts</a>
                <a href="{{ route('profile') }}" class="hover:text-slate-900">Profile</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="hover:text-slate-900">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hover:text-slate-900">Login</a>
            @endauth
        </nav>
    </div>
</header>
