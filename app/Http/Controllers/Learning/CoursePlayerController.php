<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Services\Learning\CourseAccessService;
use App\Services\Learning\VideoPlaybackService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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

        if (! $accessService->userHasActiveCourseEntitlement($request->user(), $course)) {
            abort(403);
        }

        $course->load([
            'modules.lessons' => fn ($query) => $query->published()->orderBy('sort_order'),
        ]);

        $availableLessons = $course->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->values();

        abort_if($availableLessons->isEmpty(), 404);

        $activeLesson = $lessonSlug
            ? $availableLessons->firstWhere('slug', $lessonSlug)
            : $availableLessons->first();

        abort_if(! $activeLesson instanceof CourseLesson, 404);

        $activeLesson->load('resources');

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

        $progressByLessonId = LessonProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('lesson_id', $availableLessons->pluck('id'))
            ->get()
            ->keyBy('lesson_id');

        return view('learning.player', [
            'course' => $course,
            'activeLesson' => $activeLesson,
            'activeLessonProgress' => $activeLessonProgress,
            'progressByLessonId' => $progressByLessonId,
            'streamEmbedUrl' => $activeLesson->stream_video_id
                ? $videoPlaybackService->streamEmbedUrl($activeLesson->stream_video_id)
                : null,
        ]);
    }
}
