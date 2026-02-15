<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Services\Learning\CloudflareStreamMetadataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class CourseLessonsController extends Controller
{
    public function store(
        CourseModule $module,
        Request $request,
        CloudflareStreamMetadataService $metadataService,
    ): RedirectResponse {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'summary' => ['nullable', 'string'],
            'stream_video_id' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $course = $module->course;
        $streamVideoId = ($validated['stream_video_id'] ?? null) ?: null;

        $module->lessons()->create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'slug' => $this->resolveLessonSlug(
                courseId: $course->id,
                preferredSlug: $validated['slug'] ?? null,
                title: $validated['title'],
            ),
            'summary' => $validated['summary'] ?? null,
            'stream_video_id' => $streamVideoId,
            'duration_seconds' => $this->resolveDurationSeconds($streamVideoId, $metadataService),
            'sort_order' => $validated['sort_order'] ?? ($module->lessons()->max('sort_order') + 1),
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ]);

        return to_route('admin.courses.edit', $course->id)
            ->with('status', 'Lesson created.');
    }

    public function update(
        CourseLesson $lesson,
        Request $request,
        CloudflareStreamMetadataService $metadataService,
    ): RedirectResponse {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:course_lessons,slug,'.$lesson->id.',id,course_id,'.$lesson->course_id],
            'summary' => ['nullable', 'string'],
            'stream_video_id' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'sync_duration' => ['nullable', 'boolean'],
        ]);

        $streamVideoId = ($validated['stream_video_id'] ?? null) ?: null;
        $durationSeconds = $validated['duration_seconds'] ?? null;

        if ((bool) ($validated['sync_duration'] ?? false)) {
            $durationSeconds = $this->resolveDurationSeconds($streamVideoId, $metadataService);
        }

        $lesson->forceFill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'summary' => $validated['summary'] ?? null,
            'stream_video_id' => $streamVideoId,
            'duration_seconds' => $durationSeconds,
            'sort_order' => $validated['sort_order'],
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ])->save();

        return to_route('admin.courses.edit', $lesson->course_id)
            ->with('status', 'Lesson updated.');
    }

    public function destroy(CourseLesson $lesson): RedirectResponse
    {
        $courseId = $lesson->course_id;
        $lesson->delete();

        return to_route('admin.courses.edit', $courseId)
            ->with('status', 'Lesson deleted.');
    }

    private function resolveLessonSlug(int $courseId, ?string $preferredSlug, string $title): string
    {
        $base = Str::of($preferredSlug ?: $title)->slug()->value();
        $base = $base !== '' ? $base : 'lesson';
        $candidate = $base;
        $counter = 1;

        while (CourseLesson::query()->where('course_id', $courseId)->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function resolveDurationSeconds(
        ?string $streamVideoId,
        CloudflareStreamMetadataService $metadataService,
    ): ?int {
        if (! $streamVideoId) {
            return null;
        }

        try {
            return $metadataService->durationSeconds($streamVideoId);
        } catch (RuntimeException) {
            return null;
        }
    }
}
