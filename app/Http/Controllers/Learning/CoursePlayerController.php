<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\LessonResource;
use App\Models\LessonProgress;
use App\Services\Learning\CourseAccessService;
use App\Services\Learning\VideoPlaybackService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CoursePlayerController extends Controller
{
    public function __invoke(
        Request $request,
        Course $course,
        CourseAccessService $accessService,
        VideoPlaybackService $videoPlaybackService,
        ?string $lessonSlug = null,
    ): View {
        abort_if(! $course->is_published, 404);

        abort_unless($accessService->userHasActiveCourseEntitlement($request->user(), $course), 403);

        $course->load([
            'modules.lessons' => fn ($query) => $query->published()->orderBy('sort_order'),
        ]);

        $availableLessons = $course->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->values();

        abort_if($availableLessons->isEmpty(), 404);

        $progressByLessonId = LessonProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('lesson_id', $availableLessons->pluck('id'))
            ->get()
            ->keyBy('lesson_id');

        $nextIncompleteLesson = $availableLessons->first(fn (CourseLesson $lesson): bool => $progressByLessonId->get($lesson->id)?->status !== 'completed');

        $activeLesson = $lessonSlug
            ? $availableLessons->firstWhere('slug', $lessonSlug)
            : ($nextIncompleteLesson ?? $availableLessons->first());

        abort_if(! $activeLesson instanceof CourseLesson, 404);

        $activeLesson->load('resources');

        $scopedResources = LessonResource::query()
            ->where('course_id', $course->id)
            ->where(function ($query) use ($activeLesson): void {
                $query
                    ->where(function ($courseQuery): void {
                        $courseQuery->whereNull('module_id')->whereNull('lesson_id');
                    })
                    ->orWhere(function ($moduleQuery) use ($activeLesson): void {
                        $moduleQuery
                            ->where('module_id', $activeLesson->module_id)
                            ->whereNull('lesson_id');
                    })
                    ->orWhere('lesson_id', $activeLesson->id);
            })
            ->orderBy('sort_order')
            ->get();

        $courseResources = $scopedResources->filter(
            fn (LessonResource $resource): bool => $resource->module_id === null && $resource->lesson_id === null
        )->values();
        $moduleResources = $scopedResources->filter(
            fn (LessonResource $resource): bool => $resource->module_id === $activeLesson->module_id && $resource->lesson_id === null
        )->values();
        $lessonResources = $scopedResources->filter(
            fn (LessonResource $resource): bool => $resource->lesson_id === $activeLesson->id
        )->values();

        $activeLessonProgress = LessonProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'lesson_id' => $activeLesson->id,
        ]);

        if (! $activeLessonProgress->exists) {
            $activeLessonProgress->status = 'in_progress';
            $activeLessonProgress->started_at = now();
        }

        $activeLessonProgress->last_viewed_at = now();
        $activeLessonProgress->save();

        $progressByLessonId->put($activeLesson->id, $activeLessonProgress);

        $activeLessonIndex = $availableLessons
            ->search(fn (CourseLesson $lesson): bool => $lesson->id === $activeLesson->id);

        $previousLesson = ($activeLessonIndex !== false && $activeLessonIndex > 0)
            ? $availableLessons->get($activeLessonIndex - 1)
            : null;

        $nextLesson = ($activeLessonIndex !== false)
            ? $availableLessons->get($activeLessonIndex + 1)
            : null;

        return view('learning.player', [
            'course' => $course,
            'activeLesson' => $activeLesson,
            'activeLessonSummaryHtml' => $activeLesson->summary
                ? Str::markdown($activeLesson->summary, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])
                : null,
            'activeLessonProgress' => $activeLessonProgress,
            'progressByLessonId' => $progressByLessonId,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'courseResources' => $courseResources,
            'moduleResources' => $moduleResources,
            'lessonResources' => $lessonResources,
            'videoProgressHeartbeatSeconds' => max(5, (int) config('learning.video_heartbeat_seconds', 15)),
            'streamEmbedUrl' => $activeLesson->stream_video_id
                ? $videoPlaybackService->streamEmbedUrl($activeLesson->stream_video_id)
                : null,
        ]);
    }
}
