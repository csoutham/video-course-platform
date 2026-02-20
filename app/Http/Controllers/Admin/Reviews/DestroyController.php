<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Models\CourseReview;
use App\Services\Reviews\CourseRatingAggregateService;
use Illuminate\Http\RedirectResponse;

class DestroyController extends Controller
{
    public function __invoke(CourseReview $review, CourseRatingAggregateService $aggregateService): RedirectResponse
    {
        $course = $review->course;
        $review->delete();

        if ($course) {
            $aggregateService->refreshForCourse($course);
        }

        return back()->with('status', 'Review deleted.');
    }
}
