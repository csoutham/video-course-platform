<?php

namespace App\Services\Reviews;

use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\Learning\CourseAccessService;

class CourseReviewEligibilityService
{
    public function __construct(
        private readonly CourseAccessService $accessService,
    ) {
    }

    /**
     * @return array{
     *   can_submit: bool,
     *   reason: string,
     *   progress_percent: int,
     *   required_percent: int
     * }
     */
    public function evaluate(User $user, Course $course): array
    {
        $requiredPercent = 25;

        if (! $course->is_published) {
            return [
                'can_submit' => false,
                'reason' => 'course_unpublished',
                'progress_percent' => 0,
                'required_percent' => $requiredPercent,
            ];
        }

        if (! $this->accessService->userHasActiveCourseEntitlement($user, $course)) {
            return [
                'can_submit' => false,
                'reason' => 'no_access',
                'progress_percent' => 0,
                'required_percent' => $requiredPercent,
            ];
        }

        $lessonIds = $course->lessons()
            ->published()
            ->pluck('id');

        $totalLessons = $lessonIds->count();
        if ($totalLessons === 0) {
            return [
                'can_submit' => false,
                'reason' => 'no_lessons',
                'progress_percent' => 0,
                'required_percent' => $requiredPercent,
            ];
        }

        $totalPercent = (int) LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->sum('percent_complete');

        $progressPercent = (int) floor($totalPercent / $totalLessons);
        $progressPercent = max(0, min(100, $progressPercent));

        if ($progressPercent < $requiredPercent) {
            return [
                'can_submit' => false,
                'reason' => 'insufficient_progress',
                'progress_percent' => $progressPercent,
                'required_percent' => $requiredPercent,
            ];
        }

        return [
            'can_submit' => true,
            'reason' => 'ok',
            'progress_percent' => $progressPercent,
            'required_percent' => $requiredPercent,
        ];
    }
}

