<x-slot:title>{{ $course->title }} | {{ config('app.name') }}</x-slot>
<x-slot:metaDescription>{{ $metaDescription }}</x-slot>
<x-slot:metaImage>{{ $course->thumbnail_url ?: asset('favicon.ico') }}</x-slot>
<x-slot:canonicalUrl>{{ route('courses.show', $course->slug) }}</x-slot>

@push('head')
    <script type="application/ld+json">
        {!! $courseSchemaJson !!}
    </script>
@endpush

<div class="space-y-8">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-0 lg:grid-cols-[1.35fr_minmax(320px,0.65fr)]">
            <div class="space-y-4 p-7 sm:p-8">
                <p class="vc-eyebrow">Course</p>
                <h1 class="text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">{{ $course->title }}</h1>
                <p class="text-base leading-relaxed text-slate-600">{{ $course->description }}</p>

                <div class="mt-5 flex flex-wrap gap-2 pt-3">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $moduleCount }}
                        {{ $moduleCountLabel }}
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $lessonCount }} {{ $lessonCountLabel }}
                    </span>
                    @if ($totalDurationLabel)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ $totalDurationLabel }} total video
                        </span>
                    @endif
                </div>
            </div>

            <div class="border-t border-slate-200 bg-slate-50 p-7 sm:p-8 lg:border-t-0 lg:border-l">
                <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Pricing</p>
                <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-900">
                    @if ($course->is_free)
                        Free
                    @else
                        {{ strtoupper($course->price_currency) }} {{ number_format($course->price_amount / 100, 2) }}
                    @endif
                </p>
                <p class="mt-2 text-sm text-slate-600">
                    @if ($course->is_free)
                        No payment required. Enroll instantly and start learning right away.
                    @else
                            One-time purchase. Instant access to all published lessons and resources in this course.
                    @endif
                </p>
                <ul class="mt-5 space-y-2 text-sm text-slate-600">
                    <li>
                        {{ $course->is_free ? 'Full curriculum access after enrollment' : 'Full curriculum access after checkout' }}
                    </li>
                    <li>Track progress lesson-by-lesson</li>
                    <li>Watch from any modern browser</li>
                </ul>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
        <section class="space-y-4">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-900 shadow-sm">
                @if ($introVideoEmbedUrl)
                    <iframe
                        src="{{ $introVideoEmbedUrl }}"
                        title="{{ $course->title }} intro video"
                        class="aspect-video w-full"
                        loading="lazy"
                        allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen></iframe>
                @elseif ($course->thumbnail_url)
                    <img
                        src="{{ $course->thumbnail_url }}"
                        alt="{{ $course->title }} thumbnail"
                        class="aspect-video w-full object-cover" />
                @else
                    <div class="relative aspect-video">
                        <div
                            class="absolute inset-0 bg-linear-to-br from-slate-900 via-cyan-900 to-slate-700"
                            aria-hidden="true"></div>
                        <div
                            class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(56,189,248,0.35),transparent_38%)]"></div>
                        <div
                            class="relative flex h-full items-center justify-center p-8 text-center text-2xl font-semibold text-white">
                            {{ $course->title }}
                        </div>
                    </div>
                @endif
            </article>

            @if ($longDescriptionHtml)
                <article class="vc-panel p-5">
                    <h2 class="vc-card-title">About this course</h2>
                    <div class="prose prose-slate mt-3 max-w-none text-sm">
                        {!! $longDescriptionHtml !!}
                    </div>
                </article>
            @endif

            @if ($requirementsHtml)
                <article class="vc-panel p-5">
                    <h2 class="vc-card-title">Requirements</h2>
                    <div class="prose prose-slate mt-3 max-w-none text-sm">
                        {!! $requirementsHtml !!}
                    </div>
                </article>
            @endif

            <div class="lg:hidden">
                @include('livewire.courses.partials.enrollment-panel')
            </div>

            <h2 class="vc-card-title">What you’ll learn</h2>

            @forelse ($course->modules as $module)
                <article class="vc-panel p-5">
                    <h3 class="text-base font-semibold text-slate-900">{{ $module->title }}</h3>

                    @if ($module->lessons->isEmpty())
                        <p class="mt-2 text-sm text-slate-500">No published lessons in this module yet.</p>
                    @else
                        <ol class="mt-3 space-y-2 text-sm text-slate-700">
                            @foreach ($module->lessons as $lesson)
                                <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                    <span>{{ $lesson->title }}</span>
                                    @if ($lesson->duration_label)
                                        <span class="text-xs font-semibold text-slate-500">
                                            {{ $lesson->duration_label }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </article>
            @empty
                <div class="vc-panel border-dashed p-6 text-sm text-slate-600">Curriculum will be published soon.</div>
            @endforelse
        </section>

        <aside class="hidden lg:sticky lg:top-4 lg:block">
            @include('livewire.courses.partials.enrollment-panel')
        </aside>
    </div>
</div>
