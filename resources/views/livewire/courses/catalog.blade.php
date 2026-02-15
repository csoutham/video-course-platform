<div class="space-y-6">
    <div class="vc-heading-block">
        <p class="vc-eyebrow">Course Catalog</p>
        <h1 class="vc-title">Curated courses</h1>
        <p class="vc-subtitle">Explore available training modules and purchase access to complete course tracks.</p>
    </div>

    @if ($courses->isEmpty())
        <div class="vc-panel border-dashed p-8 text-center">
            <h2 class="text-lg font-semibold text-slate-900">No published courses yet</h2>
            <p class="vc-card-copy mt-2">Check back soon for the next release.</p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($courses as $course)
                @php
                    $hasAccess = $ownedCourseIds->contains($course->id);
                    $courseLink = $hasAccess ? route('learn.show', ['course' => $course->slug]) : route('courses.show', $course->slug);
                @endphp

                <article class="vc-panel p-6">
                    <div class="space-y-2">
                        <h2 class="vc-card-title">{{ $course->title }}</h2>
                        <p class="vc-card-copy line-clamp-3">{{ $course->description }}</p>
                        <p class="text-sm font-medium text-slate-900">
                            ${{ number_format($course->price_amount / 100, 2) }}
                            {{ strtoupper($course->price_currency) }}
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ $courseLink }}" wire:navigate class="vc-link">
                            {{ $hasAccess ? 'Continue learning' : 'View details' }}
                        </a>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Published
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
