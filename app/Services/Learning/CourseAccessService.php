<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\LessonResource;
use App\Models\User;

class CourseAccessService
{
    public function userHasActiveCourseEntitlement(User $user, Course $course): bool
    {
        return $user->entitlements()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();
    }

    public function userCanAccessResource(User $user, LessonResource $resource): bool
    {
        $course = $resource->course;

        if (! $course) {
            $lesson = $resource->lesson;

            if (! $lesson || ! $lesson->is_published) {
                return false;
            }

            $course = $lesson->course;
        }

        if ($resource->lesson_id && ! $resource->lesson?->is_published) {
            return false;
        }

        if (! $course || ! $course->is_published) {
            return false;
        }

        return $this->userHasActiveCourseEntitlement($user, $course);
    }
}
