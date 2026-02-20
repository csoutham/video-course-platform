<?php

namespace App\Services\Reviews;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;

class CourseReviewService
{
    public function __construct(
        private readonly CourseRatingAggregateService $aggregateService,
    ) {
    }

    /**
     * @param  array{rating:int,title:?string,body:?string}  $input
     */
    public function upsertNativeReview(User $user, Course $course, array $input): CourseReview
    {
        $review = CourseReview::query()->firstOrNew([
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);

        $review->source = CourseReview::SOURCE_NATIVE;
        $review->reviewer_name = null;
        $review->rating = (int) $input['rating'];
        $review->title = $input['title'] ?? null;
        $review->body = $input['body'] ?? null;
        $review->status = CourseReview::STATUS_PENDING;
        $review->last_submitted_at = now();
        $review->approved_at = null;
        $review->approved_by_user_id = null;
        $review->rejected_at = null;
        $review->rejected_by_user_id = null;
        $review->hidden_at = null;
        $review->hidden_by_user_id = null;
        $review->moderation_note = null;
        $review->save();

        $this->aggregateService->refreshForCourse($course);

        return $review;
    }

    public function deleteNativeReview(User $user, Course $course): bool
    {
        $review = CourseReview::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('source', CourseReview::SOURCE_NATIVE)
            ->first();

        if (! $review) {
            return false;
        }

        $review->delete();
        $this->aggregateService->refreshForCourse($course);

        return true;
    }
}

