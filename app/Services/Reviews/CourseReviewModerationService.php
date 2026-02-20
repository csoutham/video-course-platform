<?php

namespace App\Services\Reviews;

use App\Models\CourseReview;
use App\Models\User;

class CourseReviewModerationService
{
    public function __construct(
        private readonly CourseRatingAggregateService $aggregateService,
    ) {
    }

    public function approve(CourseReview $review, User $moderator, ?string $note = null): void
    {
        $review->forceFill([
            'status' => CourseReview::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $moderator->id,
            'rejected_at' => null,
            'rejected_by_user_id' => null,
            'hidden_at' => null,
            'hidden_by_user_id' => null,
            'moderation_note' => $note,
        ])->save();

        $this->aggregateService->refreshForCourse($review->course);
    }

    public function reject(CourseReview $review, User $moderator, ?string $note = null): void
    {
        $review->forceFill([
            'status' => CourseReview::STATUS_REJECTED,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'rejected_at' => now(),
            'rejected_by_user_id' => $moderator->id,
            'hidden_at' => null,
            'hidden_by_user_id' => null,
            'moderation_note' => $note,
        ])->save();

        $this->aggregateService->refreshForCourse($review->course);
    }

    public function hide(CourseReview $review, User $moderator, ?string $note = null): void
    {
        $review->forceFill([
            'status' => CourseReview::STATUS_HIDDEN,
            'hidden_at' => now(),
            'hidden_by_user_id' => $moderator->id,
            'moderation_note' => $note,
        ])->save();

        $this->aggregateService->refreshForCourse($review->course);
    }

    public function unhide(CourseReview $review, User $moderator, ?string $note = null): void
    {
        $review->forceFill([
            'status' => CourseReview::STATUS_APPROVED,
            'hidden_at' => null,
            'hidden_by_user_id' => null,
            'approved_at' => $review->approved_at ?? now(),
            'approved_by_user_id' => $review->approved_by_user_id ?? $moderator->id,
            'moderation_note' => $note,
        ])->save();

        $this->aggregateService->refreshForCourse($review->course);
    }
}

