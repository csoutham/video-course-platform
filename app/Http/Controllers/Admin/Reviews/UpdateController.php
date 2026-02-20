<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Models\CourseReview;
use App\Services\Reviews\CourseRatingAggregateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(
        Request $request,
        CourseReview $review,
        CourseRatingAggregateService $aggregateService,
    ): RedirectResponse {
        $validated = $request->validate([
            'reviewer_name' => ['nullable', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
            'original_reviewed_at' => ['nullable', 'date'],
        ]);

        if ($review->source === \App\Models\CourseReview::SOURCE_UDEMY_MANUAL) {
            $review->reviewer_name = $validated['reviewer_name'] ?: null;
        }

        $review->rating = (int) $validated['rating'];
        $review->title = $validated['title'] ?: null;
        $review->body = $validated['body'] ?: null;
        $review->original_reviewed_at = $validated['original_reviewed_at'] ?? null;
        $review->save();

        $aggregateService->refreshForCourse($review->course);

        return back()->with('status', 'Review updated.');
    }
}
