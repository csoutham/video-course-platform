<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\Branding\BrandingService;
use App\Services\Learning\VideoPlaybackService;
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

        $course = Course::query()
            ->published()
            ->with([
                'modules.lessons' => fn ($query) => $query->published()->orderBy('sort_order'),
            ])
            ->firstWhere('slug', $this->slug);

        abort_if(! $course, 404);

        $introVideoEmbedUrl = null;
        if ($course->intro_video_id) {
            try {
                $introVideoEmbedUrl = $videoPlaybackService->streamEmbedUrl($course->intro_video_id);
            } catch (Throwable) {
                $introVideoEmbedUrl = null;
            }
        }

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

        return view('livewire.courses.detail', [
            'course' => $course,
            'giftsEnabled' => (bool) config('learning.gifts_enabled'),
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
        ]);
    }
}
