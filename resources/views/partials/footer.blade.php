<footer class="border-t border-slate-200/80 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4 text-xs text-slate-600">
        <p>{{ $branding?->platformName ?? config('app.name', 'VideoCourses') }}</p>
        <p>&copy; {{ now()->year }} All rights reserved.</p>
    </div>
</footer>
