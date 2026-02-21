<?php

namespace App\Services\Certificates;

use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\Learning\CourseAccessService;

class CourseCertificateEligibilityService
{
    public function __construct(private readonly CourseAccessService $accessService)
    {
    }

    /**
     * @return array{eligible: bool, reason: string|null, total_lessons: int, completed_lessons: int}
     */
    public function evaluate(User $user, Course $course): array
    {
        if (! (bool) config('learning.certificates_enabled')) {
            return [
                'eligible' => false,
                'reason' => 'certificates_disabled',
                'total_lessons' => 0,
                'completed_lessons' => 0,
            ];
        }

        if (! $course->is_published) {
            return [
                'eligible' => false,
                'reason' => 'course_unpublished',
                'total_lessons' => 0,
                'completed_lessons' => 0,
            ];
        }

        if (! $course->certificate_enabled || ! $course->certificate_template_path) {
            return [
                'eligible' => false,
                'reason' => 'certificate_not_configured',
                'total_lessons' => 0,
                'completed_lessons' => 0,
            ];
        }

        if (! $this->accessService->userHasActiveCourseEntitlement($user, $course)) {
            return [
                'eligible' => false,
                'reason' => 'no_access',
                'total_lessons' => 0,
                'completed_lessons' => 0,
            ];
        }

        $lessonIds = $course->lessons()
            ->published()
            ->orderBy('id')
            ->pluck('id');

        $totalLessons = $lessonIds->count();

        if ($totalLessons === 0) {
            return [
                'eligible' => false,
                'reason' => 'no_published_lessons',
                'total_lessons' => 0,
                'completed_lessons' => 0,
            ];
        }

        $completedLessons = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', 'completed')
            ->count();

        return [
            'eligible' => $completedLessons >= $totalLessons,
            'reason' => $completedLessons >= $totalLessons ? null : 'incomplete_progress',
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
        ];
    }

    public function isEligible(User $user, Course $course): bool
    {
        return (bool) $this->evaluate($user, $course)['eligible'];
    }
}
