<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Models\CourseReview;
use App\Services\Reviews\CourseReviewModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApproveController extends Controller
{
    public function __invoke(
        Request $request,
        CourseReview $review,
        CourseReviewModerationService $moderationService,
    ): RedirectResponse {
        abort_unless((bool) config('learning.reviews_enabled'), 404);

        $moderationService->approve($review, $request->user(), $request->input('moderation_note'));

        return back()->with('status', 'Review approved.');
    }
}

