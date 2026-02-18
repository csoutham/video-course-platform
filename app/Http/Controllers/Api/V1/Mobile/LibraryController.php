<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Mobile\MobileLibraryCourseResource;
use App\Models\Course;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $courseIds = $user->entitlements()->active()->pluck('course_id');

        $courses = Course::query()
            ->published()
            ->whereIn('id', $courseIds)
            ->with([
                'modules.lessons' => fn ($query) => $query->published()->orderBy('sort_order'),
            ])
            ->orderBy('title')
            ->get();

        $lessonIds = $courses
            ->flatMap(fn (Course $course) => $course->modules->flatMap(fn ($module) => $module->lessons))
            ->pluck('id')
            ->values();

        $progressByLessonId = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        $courses->each(function (Course $course) use ($progressByLessonId): void {
            $courseLessonIds = $course->modules
                ->flatMap(fn ($module) => $module->lessons)
                ->pluck('id')
                ->values();

            $courseProgressRows = $progressByLessonId
                ->only($courseLessonIds->all())
                ->values();

            $totalLessons = $courseLessonIds->count();
            $completedLessons = $courseProgressRows->where('status', 'completed')->count();
            $percentComplete = $totalLessons > 0
                ? (int) floor(($completedLessons / $totalLessons) * 100)
                : 0;

            $course->setAttribute('mobile_progress_total_lessons', $totalLessons);
            $course->setAttribute('mobile_progress_completed_lessons', $completedLessons);
            $course->setAttribute('mobile_progress_percent_complete', $percentComplete);
            $course->setAttribute('mobile_progress_last_viewed_at', $courseProgressRows->max('last_viewed_at'));
        });

        return response()->json([
            'courses' => MobileLibraryCourseResource::collection($courses),
        ]);
    }
}
