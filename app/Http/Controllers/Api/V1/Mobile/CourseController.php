<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Api\V1\Mobile\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Mobile\MobileCourseDetailResource;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Services\Learning\CourseAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use RespondsWithApiErrors;

    public function show(
        Request $request,
        string $courseSlug,
        CourseAccessService $accessService,
    ): JsonResponse {
        $course = Course::query()
            ->published()
            ->where('slug', $courseSlug)
            ->with([
                'modules.lessons' => fn ($query) => $query->published()->orderBy('sort_order'),
                'modules.lessons.resources' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->first();

        if (! $course) {
            return $this->errorResponse('course_not_found', 'Course not found.', 404);
        }

        if (! $accessService->userHasActiveCourseEntitlement($request->user(), $course)) {
            return $this->errorResponse('course_forbidden', 'You do not have access to this course.', 403);
        }

        $lessonIds = $course->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->pluck('id');

        $progressByLessonId = LessonProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        $course->setAttribute('mobile_progress_by_lesson_id', $progressByLessonId);

        return response()->json([
            'course' => new MobileCourseDetailResource($course),
        ]);
    }
}
