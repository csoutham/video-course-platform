<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Services\Learning\CourseAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CoursePlayerController extends Controller
{
    public function __invoke(
        Request $request,
        Course $course,
        CourseAccessService $accessService,
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

        return view('learning.player', [
            'course' => $course,
            'activeLesson' => $activeLesson,
        ]);
    }
}
