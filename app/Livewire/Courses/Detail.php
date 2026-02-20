<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Models\CourseReview;
use App\Services\Branding\BrandingService;
use App\Services\Billing\BillingSettingsService;
use App\Services\Learning\VideoPlaybackService;
use App\Services\Reviews\CourseReviewEligibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('components.public-layout')]
class Detail extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render(VideoPlaybackService $videoPlaybackService): View
    {
        $branding = resolve(BrandingService::class)->current();
        $billingSettings = resolve(BillingSettingsService::class)->current();
        $reviewsEnabled = (bool) config('learning.reviews_enabled');

        $course = Course::query()
            ->published()
            ->with([
                'modules.lessons' => fn ($query) => $query->published()->orderBy('sort_order'),
            ])
            ->firstWhere('slug', $this->slug);

        abort_if(! $course, 404);

        $approvedReviews = collect();
        $viewerReview = null;
        $reviewEligibility = null;
        if ($reviewsEnabled) {
            $approvedReviews = CourseReview::query()
                ->where('course_id', $course->id)
                ->approved()
                ->with(['user:id,name'])
                ->orderByDesc('original_reviewed_at')
                ->orderByDesc('approved_at')
                ->orderByDesc('created_at')
                ->limit(12)
                ->get();

            if (auth()->check()) {
                $viewerReview = CourseReview::query()
                    ->where('course_id', $course->id)
                    ->where('user_id', auth()->id())
                    ->first();

                $reviewEligibility = resolve(CourseReviewEligibilityService::class)->evaluate(auth()->user(), $course);
            }
        }

        $introVideoEmbedUrl = null;
        if ($course->intro_video_id) {
            try {
                $introVideoEmbedUrl = $videoPlaybackService->streamEmbedUrl($course->intro_video_id);
            } catch (Throwable) {
                $introVideoEmbedUrl = null;
            }
        }

        $reviewSchema = $approvedReviews
            ->take(5)
            ->map(fn (CourseReview $review): array => [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $review->public_reviewer_name,
                ],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => (int) $review->rating,
                    'bestRating' => 5,
                ],
                'name' => (string) ($review->title ?: 'Course review'),
                'reviewBody' => (string) ($review->body ?: ''),
                'datePublished' => $review->display_date?->toDateString(),
            ])
            ->values()
            ->all();

        $courseSchemaJson = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title,
            'description' => Str::limit(strip_tags((string) ($course->long_description ?: $course->description)), 160),
            'image' => $course->thumbnail_url ?: asset('favicon.ico'),
            'url' => route('courses.show', $course->slug),
            'provider' => [
                '@type' => 'Organization',
                'name' => $branding->platformName,
                'url' => url('/'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => strtoupper((string) $course->price_currency),
                'price' => $course->is_free ? '0.00' : number_format($course->price_amount / 100, 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'url' => route('courses.show', $course->slug),
            ],
            'aggregateRating' => (int) $course->reviews_approved_count > 0
                ? [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (float) ($course->rating_average ?? 0),
                    'reviewCount' => (int) $course->reviews_approved_count,
                ]
                : null,
            'review' => $reviewSchema,
            'video' => $introVideoEmbedUrl
                ? [
                    '@type' => 'VideoObject',
                    'name' => $course->title.' Intro',
                    'embedUrl' => $introVideoEmbedUrl,
                ]
                : null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $lessonCount = (int) $course->modules->sum(fn ($module) => $module->lessons->count());
        $totalDurationSeconds = (int) $course->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->sum(fn ($lesson) => (int) ($lesson->duration_seconds ?? 0));

        $totalDurationLabel = $totalDurationSeconds > 0
            ? sprintf(
                '%dh %02dm',
                intdiv($totalDurationSeconds, 3600),
                intdiv($totalDurationSeconds % 3600, 60),
            )
            : null;

        $preordersEnabled = (bool) config('learning.preorders_enabled');
        $isPreorderMode = $preordersEnabled && $course->is_preorder_enabled && ! $course->isReleased();
        $isPreorderWindowActive = $isPreorderMode && $course->isPreorderWindowActive();
        $preorderPriceAmount = (int) ($course->preorder_price_amount ?? 0);
        $ratingDistribution = collect(range(1, 5))
            ->mapWithKeys(fn (int $value): array => [(string) $value => (int) ($course->rating_distribution_json[(string) $value] ?? 0)])
            ->all();

        return view('livewire.courses.detail', [
            'course' => $course,
            'giftsEnabled' => (bool) config('learning.gifts_enabled'),
            'subscriptionsEnabled' => (bool) config('learning.subscriptions_enabled'),
            'reviewsEnabled' => $reviewsEnabled,
            'subscriptionMonthlyPriceId' => $billingSettings->stripe_subscription_monthly_price_id,
            'subscriptionYearlyPriceId' => $billingSettings->stripe_subscription_yearly_price_id,
            'preordersEnabled' => $preordersEnabled,
            'isPreorderMode' => $isPreorderMode,
            'isPreorderWindowActive' => $isPreorderWindowActive,
            'preorderPriceAmount' => $preorderPriceAmount,
            'courseSchemaJson' => $courseSchemaJson,
            'introVideoEmbedUrl' => $introVideoEmbedUrl,
            'metaDescription' => Str::limit(strip_tags((string) ($course->long_description ?: $course->description)), 155),
            'longDescriptionHtml' => $course->long_description
                ? Str::markdown($course->long_description, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])
                : null,
            'requirementsHtml' => $course->requirements
                ? Str::markdown($course->requirements, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])
                : null,
            'moduleCount' => $course->modules->count(),
            'moduleCountLabel' => Str::plural('module', $course->modules->count()),
            'lessonCount' => $lessonCount,
            'lessonCountLabel' => Str::plural('lesson', $lessonCount),
            'totalDurationLabel' => $totalDurationLabel,
            'approvedReviews' => $approvedReviews,
            'viewerReview' => $viewerReview,
            'reviewEligibility' => $reviewEligibility,
            'ratingDistribution' => $ratingDistribution,
        ]);
    }
}
