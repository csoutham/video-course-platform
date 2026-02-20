<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Reviews\CourseReviewEligibilityService;
use App\Services\Reviews\CourseReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(
        Request $request,
        Course $course,
        CourseReviewEligibilityService $eligibilityService,
        CourseReviewService $reviewService,
    ): RedirectResponse {
        abort_unless((bool) config('learning.reviews_enabled'), 404);
        abort_if(! $course->is_published, 404);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $eligibility = $eligibilityService->evaluate($request->user(), $course);
        if (! $eligibility['can_submit']) {
            return back()->withErrors([
                'review' => $eligibility['reason'] === 'insufficient_progress'
                    ? "You can review this course after reaching {$eligibility['required_percent']}% progress. Current progress: {$eligibility['progress_percent']}%."
                    : 'You are not eligible to review this course yet.',
            ]);
        }

        $reviewService->upsertNativeReview($request->user(), $course, $validated);

        return to_route('courses.show', $course->slug)
            ->with('status', 'Thanks. Your review was submitted and is pending moderation.');
    }
}

