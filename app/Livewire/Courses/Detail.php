<?php

namespace App\Livewire\Courses;

use App\Models\Course;
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
            'description' => Str::limit(strip_tags((string) $course->description), 160),
            'image' => $course->thumbnail_url ?: asset('favicon.ico'),
            'url' => route('courses.show', $course->slug),
            'provider' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => url('/'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => strtoupper((string) $course->price_currency),
                'price' => number_format($course->price_amount / 100, 2, '.', ''),
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

        return view('livewire.courses.detail', [
            'course' => $course,
            'giftsEnabled' => (bool) config('learning.gifts_enabled'),
            'courseSchemaJson' => $courseSchemaJson,
            'introVideoEmbedUrl' => $introVideoEmbedUrl,
        ]);
    }
}
