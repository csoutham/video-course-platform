<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\LessonResource;
use App\Models\User;

class CourseAccessService
{
    public function userHasActiveCourseEntitlement(User $user, Course $course): bool
    {
        $hasEntitlement = $user->entitlements()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        if ($hasEntitlement) {
            return true;
        }

        if (! (bool) config('learning.subscriptions_enabled')) {
            return false;
        }

        if (! $course->is_published || $course->is_subscription_excluded) {
            return false;
        }

        return $user->subscriptions()
            ->where(function ($query): void {
                $query
                    ->whereIn('status', ['active', 'trialing'])
                    ->orWhere(function ($canceled): void {
                        $canceled->where('status', 'canceled')->where('current_period_end', '>', now());
                    });
            })
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
