<x-slot:title>
    Professional Video Courses | {{ $branding?->platformName ?? config('app.name') }}
</x-slot>
<x-slot:metaDescription>
    Upgrade practical skills with focused, purchasable video courses. Instant checkout, structured modules, and
    downloadable resources.
</x-slot>

@push('head')
    <script type="application/ld+json">
        {!! $catalogSchemaJson !!}
    </script>
@endpush

<div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div
            class="pointer-events-none absolute -top-28 -right-20 h-72 w-72 rounded-full bg-linear-to-br from-cyan-200/70 to-transparent blur-3xl"></div>
        <div
            class="pointer-events-none absolute -bottom-28 -left-20 h-72 w-72 rounded-full bg-linear-to-tr from-slate-300/40 to-transparent blur-3xl"></div>

        <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:items-end">
            <div class="space-y-4">
                <p class="vc-eyebrow">{{ $branding?->homepageEyebrow ?? 'Professional Training' }}</p>
                <h1 class="text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">
                    {{ $branding?->homepageTitle ?? 'Learn faster with curated, results-focused courses.' }}
                </h1>
                <p class="max-w-3xl text-base text-slate-600">
                    {{ $branding?->homepageSubtitle ?? 'Each course is designed for implementation. Buy once, get immediate access, and follow clear module-based lessons with downloadable resources.' }}
                </p>
            </div>
        </div>
    </section>

    @if ($courses->isEmpty())
        <div class="vc-panel border-dashed p-8 text-center">
            <h2 class="text-lg font-semibold text-slate-900">No published courses yet</h2>
            <p class="vc-card-copy mt-2">Check back soon for the next release.</p>
        </div>
    @else
        <section class="grid gap-6 md:grid-cols-2">
            @foreach ($courseCards as $card)
                <article
                    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <a href="{{ $card['courseLink'] }}" wire:navigate class="block">
                        <div class="relative aspect-video overflow-hidden bg-slate-900">
                            @if ($card['thumbnail'])
                                <img
                                    src="{{ $card['thumbnail'] }}"
                                    alt="{{ $card['course']->title }} thumbnail"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                            @else
                                <div
                                    class="flex h-full w-full items-center justify-center bg-linear-to-br from-slate-800 via-cyan-900 to-slate-700 px-6 text-center text-xl font-semibold tracking-tight text-white">
                                    {{ $card['course']->title }}
                                </div>
                            @endif

                            <div
                                class="absolute right-3 bottom-3 rounded-full bg-white px-3 py-1 text-sm font-bold text-slate-900 shadow">
                                {{ strtoupper($card['course']->price_currency) }}
                                {{ number_format($card['course']->price_amount / 100, 2) }}
                            </div>
                        </div>
                    </a>

                    <div class="space-y-3 p-5">
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">
                            {{ $card['course']->title }}
                        </h2>
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            @if ($card['reviewCount'] > 0 && $card['ratingAverage'] !== null)
                                <span class="font-semibold text-slate-900">{{ number_format($card['ratingAverage'], 1) }}</span>
                                <span aria-hidden="true">★</span>
                                <span>
                                    ({{ $card['reviewCount'] }}
                                    {{ \Illuminate\Support\Str::plural('review', $card['reviewCount']) }})
                                </span>
                            @else
                                <span>No reviews yet</span>
                            @endif
                        </div>
                        <p class="line-clamp-3 text-sm leading-relaxed text-slate-600">
                            {{ $card['course']->description }}
                        </p>

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <a href="{{ $card['courseLink'] }}" wire:navigate class="vc-btn-primary">
                                {{ $card['hasAccess'] ? 'Continue learning' : 'View course and pricing' }}
                            </a>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                {{ $card['hasAccess'] ? 'Owned' : 'Available now' }}
                            </span>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</div>
