<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Api\V1\Mobile\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Mobile\MobileLessonProgressResource;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Services\Learning\CourseAccessService;
use App\Services\Learning\VideoPlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonPlaybackController extends Controller
{
    use RespondsWithApiErrors;

    public function show(
        Request $request,
        string $courseSlug,
        string $lessonSlug,
        CourseAccessService $accessService,
        VideoPlaybackService $videoPlaybackService,
    ): JsonResponse {
        $course = Course::query()->published()->where('slug', $courseSlug)->first();

        if (! $course) {
            return $this->errorResponse('course_not_found', 'Course not found.', 404);
        }

        if (! $accessService->userHasActiveCourseEntitlement($request->user(), $course)) {
            return $this->errorResponse('course_forbidden', 'You do not have access to this course.', 403);
        }

        $lesson = $course->lessons()->published()->where('slug', $lessonSlug)->first();

        if (! $lesson) {
            return $this->errorResponse('lesson_not_found', 'Lesson not found.', 404);
        }

        $progress = LessonProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ]);

        if (! $progress->exists) {
            $progress->status = 'in_progress';
            $progress->started_at = now();
        }

        $progress->last_viewed_at = now();
        $progress->save();

        $resources = LessonResource::query()
            ->where('course_id', $course->id)
            ->where(function ($query) use ($lesson): void {
                $query
                    ->where(function ($courseQuery): void {
                        $courseQuery->whereNull('module_id')->whereNull('lesson_id');
                    })
                    ->orWhere(function ($moduleQuery) use ($lesson): void {
                        $moduleQuery
                            ->where('module_id', $lesson->module_id)
                            ->whereNull('lesson_id');
                    })
                    ->orWhere('lesson_id', $lesson->id);
            })
            ->orderBy('sort_order')
            ->get();

        $streamUrl = null;
        $stream = null;

        if ($lesson->stream_video_id) {
            $stream = $videoPlaybackService->playbackUrls($lesson->stream_video_id);
            $streamUrl = $stream['preferred_url'];
        }

        return response()->json([
            'stream_url' => $streamUrl,
            'stream' => $stream,
            'heartbeat_seconds' => max(5, (int) config('learning.video_heartbeat_seconds', 15)),
            'auto_complete_percent' => max(1, min(100, (int) config('learning.video_autocomplete_percent', 90))),
            'lesson' => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'duration_seconds' => $lesson->duration_seconds,
                'updated_at' => $lesson->updated_at?->toIso8601String(),
                'resources' => $resources->map(fn(LessonResource $resource) => [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'mime_type' => $resource->mime_type,
                    'size_bytes' => $resource->size_bytes,
                    'updated_at' => $resource->updated_at?->toIso8601String(),
                ])->values(),
            ],
            'progress' => new MobileLessonProgressResource($progress),
        ]);
    }

    public function progress(
        Request $request,
        string $courseSlug,
        string $lessonSlug,
        CourseAccessService $accessService,
    ): JsonResponse {
        $course = Course::query()->published()->where('slug', $courseSlug)->first();

        if (! $course) {
            return $this->errorResponse('course_not_found', 'Course not found.', 404);
        }

        if (! $accessService->userHasActiveCourseEntitlement($request->user(), $course)) {
            return $this->errorResponse('course_forbidden', 'You do not have access to this course.', 403);
        }

        $lesson = $course->lessons()->published()->where('slug', $lessonSlug)->first();

        if (! $lesson) {
            return $this->errorResponse('lesson_not_found', 'Lesson not found.', 404);
        }

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
            'percent_complete' => (int) $progress->percent_complete,
            'playback_position_seconds' => (int) $progress->playback_position_seconds,
            'updated_at' => $progress->updated_at?->toIso8601String(),
        ]);
    }
}
