<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response|View
    {
        $courseUrls = Course::query()
            ->published()
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at'])
            ->map(fn (Course $course): array => [
                'loc' => route('courses.show', $course->slug),
                'lastmod' => $course->updated_at?->toAtomString(),
            ]);

        $urls = collect([
            [
                'loc' => route('courses.index'),
                'lastmod' => now()->toAtomString(),
            ],
        ])->merge($courseUrls);

        return response()
            ->view('seo.sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
