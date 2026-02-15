<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Services\Learning\CourseAccessService;
use Illuminate\Http\JsonResponse;
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

        abort_unless($accessService->userHasActiveCourseEntitlement($request->user(), $course), 403);

        $lesson = $course->lessons()
            ->published()
            ->where('slug', $lessonSlug)
            ->firstOrFail();

        $progress = LessonProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ]);

        $progress->started_at ??= now();
        $progress->last_viewed_at = now();

        if ($progress->status === 'completed') {
            $progress->status = 'in_progress';
            $progress->completed_at = null;
            $progress->percent_complete = min(99, (int) ($progress->percent_complete ?? 0));
        } else {
            $progress->status = 'completed';
            $progress->completed_at = now();
            $progress->percent_complete = max(100, (int) ($progress->percent_complete ?? 0));
        }

        $progress->save();

        return to_route('learn.show', [
            'course' => $course->slug,
            'lessonSlug' => $lesson->slug,
        ]);
    }

    public function video(
        Request $request,
        Course $course,
        string $lessonSlug,
        CourseAccessService $accessService,
    ): JsonResponse {
        abort_if(! $course->is_published, 404);

        abort_unless($accessService->userHasActiveCourseEntitlement($request->user(), $course), 403);

        $lesson = $course->lessons()
            ->published()
            ->where('slug', $lessonSlug)
            ->firstOrFail();

        $validated = $request->validate([
            'position_seconds' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

        $position = (int) $validated['position_seconds'];
        $duration = isset($validated['duration_seconds']) ? (int) $validated['duration_seconds'] : null;

        $progress = LessonProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ]);

        $progress->started_at ??= now();
        $progress->status = $progress->status ?: 'in_progress';
        $progress->last_viewed_at = now();
        $progress->playback_position_seconds = $position;

        if ($duration !== null) {
            $progress->video_duration_seconds = $duration;
            $calculatedPercent = (int) floor(($position / $duration) * 100);
            $progress->percent_complete = max(0, min(100, $calculatedPercent));
        } else {
            $progress->percent_complete = max(0, min(100, (int) ($progress->percent_complete ?? 0)));
        }

        $autoCompletePercent = max(1, min(100, (int) config('learning.video_autocomplete_percent', 90)));
        $isCompleted = (bool) ($validated['is_completed'] ?? false)
            || ($progress->percent_complete >= $autoCompletePercent);

        if ($isCompleted) {
            $progress->status = 'completed';
            $progress->completed_at ??= now();
            $progress->percent_complete = max($progress->percent_complete, 100);
        }

        $progress->save();

        return response()->json([
            'status' => $progress->status,
            'percent_complete' => $progress->percent_complete,
        ]);
    }
}
