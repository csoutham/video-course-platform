<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Services\Learning\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    public function complete(
        Request $request,
        Course $course,
        string $lessonSlug,
        CourseAccessService $accessService,
    ): RedirectResponse {
        abort_if(! $course->is_published, 404);

        if (! $accessService->userHasActiveCourseEntitlement($request->user(), $course)) {
            abort(403);
        }

        $lesson = $course->lessons()
            ->published()
            ->where('slug', $lessonSlug)
            ->firstOrFail();

        $progress = LessonProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ]);

        $progress->status = 'completed';
        $progress->started_at ??= now();
        $progress->last_viewed_at = now();
        $progress->completed_at = now();
        $progress->save();

        return redirect()->route('learn.show', [
            'course' => $course->slug,
            'lessonSlug' => $lesson->slug,
        ]);
    }
}
