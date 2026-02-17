<x-slot:title>{{ $course->title }} | {{ config('app.name') }}</x-slot>
<x-slot:metaDescription>
    {{ \Illuminate\Support\Str::limit(strip_tags($course->long_description ?: $course->description), 155) }}
</x-slot>
<x-slot:metaImage>{{ $course->thumbnail_url ?: asset('favicon.ico') }}</x-slot>
<x-slot:canonicalUrl>{{ route('courses.show', $course->slug) }}</x-slot>

@push('head')
    <script type="application/ld+json">
        {!! $courseSchemaJson !!}
    </script>
@endpush

@php
    $longDescriptionHtml = $course->long_description
        ? \Illuminate\Support\Str::markdown($course->long_description, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ])
        : null;
    $requirementsHtml = $course->requirements
        ? \Illuminate\Support\Str::markdown($course->requirements, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ])
        : null;
    $lessonCount = $course->modules->sum(fn ($module) => $module->lessons->count());
    $totalDurationSeconds = (int) $course->modules
        ->flatMap(fn ($module) => $module->lessons)
        ->sum(fn ($lesson) => (int) ($lesson->duration_seconds ?? 0));
    $totalDurationLabel =
        $totalDurationSeconds > 0
            ? sprintf(
                '%dh %02dm',
                intdiv($totalDurationSeconds, 3600),
                intdiv($totalDurationSeconds % 3600, 60),
            )
            : null;
@endphp

<div class="space-y-8">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-0 lg:grid-cols-[1.35fr_minmax(320px,0.65fr)]">
            <div class="space-y-4 p-7 sm:p-8">
                <p class="vc-eyebrow">Course</p>
                <h1 class="text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">{{ $course->title }}</h1>
                <p class="text-base leading-relaxed text-slate-600">{{ $course->description }}</p>

                <div class="mt-5 flex flex-wrap gap-2 pt-3">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $course->modules->count() }}
                        {{ \Illuminate\Support\Str::plural('module', $course->modules->count()) }}
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $lessonCount }} {{ \Illuminate\Support\Str::plural('lesson', $lessonCount) }}
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
                    {{ strtoupper($course->price_currency) }} {{ number_format($course->price_amount / 100, 2) }}
                </p>
                <p class="mt-2 text-sm text-slate-600">
                    One-time purchase. Instant access to all published lessons and resources in this course.
                </p>
                <ul class="mt-5 space-y-2 text-sm text-slate-600">
                    <li>Full curriculum access after checkout</li>
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
                            class="absolute inset-0 bg-gradient-to-br from-slate-900 via-cyan-900 to-slate-700"
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
                                    @if ($lesson->duration_seconds)
                                        <span class="text-xs font-semibold text-slate-500">
                                            {{ gmdate('i:s', $lesson->duration_seconds) }}
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

        <aside class="lg:sticky lg:top-4">
            <div class="vc-panel space-y-4 p-6">
                <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Enrollment</p>
                <p class="text-sm text-slate-600">Complete checkout to unlock this course immediately.</p>

                <form
                    method="POST"
                    action="{{ route('checkout.start', $course) }}"
                    class="space-y-3"
                    x-data="{ isGift: {{ old('is_gift') ? 'true' : 'false' }} }">
                    @csrf

                    @guest
                        <div>
                            <label
                                for="email"
                                class="block text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                                Email
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                required
                                value="{{ old('email') }}"
                                class="vc-input"
                                placeholder="you@example.com" />
                            @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endguest

                    @if ($giftsEnabled)
                        <div class="vc-panel-soft p-3">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                                <input
                                    type="checkbox"
                                    name="is_gift"
                                    value="1"
                                    class="rounded border-slate-300"
                                    @checked(old('is_gift'))
                                    x-model="isGift" />
                                Gift this course
                            </label>
                            <p class="mt-1 text-xs text-slate-500">
                                Recipient will get a claim link after payment confirmation.
                            </p>
                        </div>

                        <div class="vc-panel-soft space-y-3 p-3" x-show="isGift" x-cloak>
                            <div>
                                <label
                                    for="recipient_email"
                                    class="block text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                                    Recipient email
                                </label>
                                <input
                                    id="recipient_email"
                                    name="recipient_email"
                                    type="email"
                                    value="{{ old('recipient_email') }}"
                                    class="vc-input"
                                    placeholder="friend@example.com" />
                                @error('recipient_email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="recipient_name"
                                    class="block text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                                    Recipient name (optional)
                                </label>
                                <input
                                    id="recipient_name"
                                    name="recipient_name"
                                    type="text"
                                    value="{{ old('recipient_name') }}"
                                    class="vc-input"
                                    placeholder="Jane Doe" />
                            </div>

                            <div>
                                <label
                                    for="gift_message"
                                    class="block text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                                    Message (optional)
                                </label>
                                <textarea
                                    id="gift_message"
                                    name="gift_message"
                                    rows="3"
                                    maxlength="500"
                                    class="vc-input"
                                    placeholder="Enjoy this course!">
{{ old('gift_message') }}</textarea
                                >
                                @error('gift_message')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <div>
                        <label
                            for="promotion_code"
                            class="block text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">
                            Promotion code (optional)
                        </label>
                        <input
                            id="promotion_code"
                            name="promotion_code"
                            type="text"
                            value="{{ old('promotion_code') }}"
                            class="vc-input"
                            placeholder="promo_xxx" />
                    </div>

                    <button type="submit" class="vc-btn-primary w-full justify-center py-2.5">
                        {{ $giftsEnabled ? 'Continue to secure checkout' : 'Buy course now' }}
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>
