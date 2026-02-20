<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Reviews\CourseReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    public function __invoke(Request $request, Course $course, CourseReviewService $reviewService): RedirectResponse
    {
        abort_unless((bool) config('learning.reviews_enabled'), 404);
        abort_if(! $course->is_published, 404);

        $reviewService->deleteNativeReview($request->user(), $course);

        return to_route('courses.show', $course->slug)
            ->with('status', 'Your review has been removed.');
    }
}

