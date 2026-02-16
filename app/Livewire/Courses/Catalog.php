<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout')]
class Catalog extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $ownedCourseIds = collect();

        if ($user) {
            $ownedCourseIds = $user->entitlements()
                ->active()
                ->pluck('course_id');
        }

        $courses = Course::query()
            ->published()
            ->orderBy('title')
            ->get();

        $catalogSchemaJson = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Professional Video Courses',
            'itemListElement' => $courses->values()->map(
                fn (Course $course, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => [
                        '@type' => 'Course',
                        'name' => $course->title,
                        'description' => Str::limit(strip_tags((string) $course->description), 160),
                        'url' => route('courses.show', $course->slug),
                        'image' => $course->thumbnail_url ?: asset('favicon.ico'),
                        'offers' => [
                            '@type' => 'Offer',
                            'priceCurrency' => strtoupper($course->price_currency),
                            'price' => number_format($course->price_amount / 100, 2, '.', ''),
                            'availability' => 'https://schema.org/InStock',
                            'url' => route('courses.show', $course->slug),
                        ],
                    ],
                ],
            )->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return view('livewire.courses.catalog', [
            'courses' => $courses,
            'ownedCourseIds' => $ownedCourseIds,
            'catalogSchemaJson' => $catalogSchemaJson,
        ]);
    }
}
