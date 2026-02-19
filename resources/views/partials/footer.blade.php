<footer class="border-t border-slate-200/80 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-col gap-1 px-6 py-4 text-xs text-slate-600 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            @if ($branding?->publisherWebsite)
                <a href="{{ $branding->publisherWebsite }}" target="_blank" rel="noopener noreferrer" class="vc-link text-xs">
                    {{ $branding->publisherName }}
                </a>
            @else
                <p>{{ $branding?->publisherName ?? config('app.name', 'VideoCourses') }}</p>
            @endif

            @if ($branding?->footerTagline)
                <p class="text-[11px] text-slate-500">{{ $branding->footerTagline }}</p>
            @endif
        </div>
        <p>&copy; {{ now()->year }} All rights reserved.</p>
    </div>
</footer>
