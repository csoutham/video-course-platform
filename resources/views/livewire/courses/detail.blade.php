<x-slot:title>
    {{ $course->title }} | {{ $branding?->platformName ?? config('app.name') }}
</x-slot>
<x-slot:metaDescription>{{ $metaDescription }}</x-slot>
<x-slot:metaImage>{{ $course->thumbnail_url ?: asset('favicon.ico') }}</x-slot>
<x-slot:canonicalUrl>{{ route('courses.show', $course->slug) }}</x-slot>

@push('head')
    <script type="application/ld+json">
        {!! $courseSchemaJson !!}
    </script>
@endpush

<div class="space-y-8">
    @if (session('status'))
        <div class="vc-alert vc-alert-success">{{ session('status') }}</div>
    @endif

    @error('review')
        <div class="vc-alert vc-alert-warning">{{ $message }}</div>
    @enderror

    @if (request()->query('preorder') === 'reserved')
        <div class="vc-alert vc-alert-success">Preorder reserved. You will be charged automatically at release.</div>
    @elseif (request()->query('preorder') === 'cancel')
        <div class="vc-alert vc-alert-warning">Preorder checkout was canceled.</div>
    @endif

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
                    @if ($reviewsEnabled && $course->reviews_approved_count > 0 && $course->rating_average !== null)
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                            {{ number_format((float) $course->rating_average, 1) }} ★
                            ({{ $course->reviews_approved_count }}
                            {{ \Illuminate\Support\Str::plural('review', $course->reviews_approved_count) }})
                        </span>
                    @endif
                </div>
            </div>

            <div class="border-t border-slate-200 bg-slate-50 p-7 sm:p-8 lg:border-t-0 lg:border-l">
                <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Pricing</p>
                <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-900">
                    @if ($isPreorderMode ?? false)
                        {{ strtoupper($course->price_currency) }}
                        {{ number_format(($preorderPriceAmount ?? 0) / 100, 2) }}
                    @elseif ($course->is_free)
                        Free
                    @else
                        {{ strtoupper($course->price_currency) }} {{ number_format($course->price_amount / 100, 2) }}
                    @endif
                </p>
                <p class="mt-2 text-sm text-slate-600">
                    @if ($isPreorderMode ?? false)
                        Reserve this course now. Payment is captured automatically when the course is released.
                    @elseif ($course->is_free)
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

            @if ($reviewsEnabled)
                <article class="vc-panel p-5">
                    <h2 class="vc-card-title">Ratings and Reviews</h2>

                    <div class="mt-3 grid gap-4 lg:grid-cols-[minmax(0,0.45fr)_minmax(0,1fr)]">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm text-slate-600">Average rating</p>
                            <p class="mt-1 text-3xl font-semibold text-slate-900">
                                {{ $course->rating_average !== null ? number_format((float) $course->rating_average, 1) : 'N/A' }}
                            </p>
                            <p class="text-sm text-slate-600">
                                {{ $course->reviews_approved_count }}
                                {{ \Illuminate\Support\Str::plural('approved review', $course->reviews_approved_count) }}
                            </p>

                            <div class="mt-3 space-y-2">
                                @foreach (array_reverse(range(1, 5)) as $ratingValue)
                                    @php
                                        $count = $ratingDistribution[(string) $ratingValue] ?? 0;
                                        $percentage =
                                            $course->reviews_approved_count > 0
                                                ? (int) round(($count / $course->reviews_approved_count) * 100)
                                                : 0;
                                    @endphp
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-10 font-medium text-slate-700">{{ $ratingValue }}★</span>
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
                                            <div
                                                class="h-full rounded-full bg-amber-400"
                                                style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <span class="w-10 text-right text-slate-500">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-3">
                            @auth
                                @if ($reviewEligibility && $reviewEligibility['can_submit'])
                                    <form method="POST" action="{{ route('courses.reviews.store', $course) }}" class="space-y-3">
                                        @csrf
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="vc-label" for="review_rating">Rating</label>
                                                <select id="review_rating" name="rating" class="vc-input" required>
                                                    @foreach (range(5, 1) as $ratingOption)
                                                        <option
                                                            value="{{ $ratingOption }}"
                                                            @selected((int) old('rating', $viewerReview?->rating ?? 5) === $ratingOption)>
                                                            {{ $ratingOption }} stars
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="vc-label" for="review_title">Title (optional)</label>
                                                <input
                                                    id="review_title"
                                                    name="title"
                                                    class="vc-input"
                                                    maxlength="120"
                                                    value="{{ old('title', $viewerReview?->title) }}" />
                                            </div>
                                        </div>

                                        <div>
                                            <label class="vc-label" for="review_body">Review (optional)</label>
                                            <textarea
                                                id="review_body"
                                                name="body"
                                                rows="4"
                                                class="vc-input"
                                                maxlength="2000">{{ old('body', $viewerReview?->body) }}</textarea>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <button class="vc-btn-primary" type="submit">
                                                {{ $viewerReview ? 'Update review' : 'Submit review' }}
                                            </button>

                                            @if ($viewerReview?->status === \App\Models\CourseReview::STATUS_PENDING)
                                                <span
                                                    class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                                    Pending moderation
                                                </span>
                                            @elseif($viewerReview?->status === \App\Models\CourseReview::STATUS_REJECTED)
                                                <span
                                                    class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                                    Needs update before approval
                                                </span>
                                            @endif
                                        </div>
                                    </form>

                                    @if ($viewerReview)
                                        <form method="POST" action="{{ route('courses.reviews.destroy', $course) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                                                type="submit">
                                                Remove review
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                                        @if ($reviewEligibility && $reviewEligibility['reason'] === 'insufficient_progress')
                                            You can review this course at
                                            {{ $reviewEligibility['required_percent'] }}% progress. Current progress:
                                            {{ $reviewEligibility['progress_percent'] }}%.
                                        @else
                                            Reviews are available to active learners once eligibility requirements are
                                            met.
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                                    Please sign in and start learning to leave a rating and review.
                                </div>
                            @endauth

                            @if ($approvedReviews->isNotEmpty())
                                <div class="space-y-3 pt-1">
                                    @foreach ($approvedReviews as $review)
                                        <article class="rounded-xl border border-slate-200 bg-white p-4">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-sm font-semibold text-slate-900">
                                                    {{ $review->public_reviewer_name }}
                                                </p>
                                                <p class="text-xs text-slate-500">
                                                    {{ $review->display_date?->format('M j, Y') }}
                                                </p>
                                            </div>
                                            <p class="mt-1 text-sm font-semibold text-amber-700">{{ $review->rating }} ★</p>
                                            @if ($review->title)
                                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $review->title }}</p>
                                            @endif
                                            @if ($review->body)
                                                <p class="mt-1 text-sm leading-relaxed text-slate-700">{{ $review->body }}</p>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-slate-600">No approved reviews yet.</p>
                            @endif
                        </div>
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
