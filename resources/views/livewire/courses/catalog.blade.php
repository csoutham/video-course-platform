<div class="space-y-6">
    <div class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Course Catalog</p>
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Curated courses</h1>
        <p class="max-w-2xl text-sm text-slate-600">
            Explore available training modules and purchase access to complete course tracks.
        </p>
    </div>

    @if ($courses->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
            <h2 class="text-lg font-semibold text-slate-900">No published courses yet</h2>
            <p class="mt-2 text-sm text-slate-600">Check back soon for the next release.</p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($courses as $course)
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-slate-900">{{ $course->title }}</h2>
                        <p class="line-clamp-3 text-sm text-slate-600">{{ $course->description }}</p>
                        <p class="text-sm font-medium text-slate-900">
                            ${{ number_format($course->price_amount / 100, 2) }} {{ strtoupper($course->price_currency) }}
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('courses.show', $course->slug) }}" wire:navigate class="text-sm font-semibold text-slate-900 hover:text-slate-700">
                            View details
                        </a>
                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Published</span>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
