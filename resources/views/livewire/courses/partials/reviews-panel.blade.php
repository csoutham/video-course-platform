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
                                <div class="h-full rounded-full bg-amber-400" style="width: {{ $percentage }}%"></div>
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
                                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                        Pending moderation
                                    </span>
                                @elseif ($viewerReview?->status === \App\Models\CourseReview::STATUS_REJECTED)
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
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
                                You can review this course at {{ $reviewEligibility['required_percent'] }}% progress.
                                Current progress: {{ $reviewEligibility['progress_percent'] }}%.
                            @else
                                Reviews are available to active learners once eligibility requirements are met.
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