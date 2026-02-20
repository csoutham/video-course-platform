<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResourcesController extends Controller
{
    public function storeForCourse(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'resource_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->storeResource(
            course: $course,
            module: null,
            lesson: null,
            file: $validated['resource_file'],
            name: $validated['name'] ?? null,
        );

        return to_route('admin.courses.edit', $course)->with('status', 'Course resource uploaded.');
    }

    public function storeForModule(Request $request, CourseModule $module): RedirectResponse
    {
        $validated = $request->validate([
            'resource_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->storeResource(
            course: $module->course,
            module: $module,
            lesson: null,
            file: $validated['resource_file'],
            name: $validated['name'] ?? null,
        );

        return to_route('admin.courses.edit', $module->course_id)->with('status', 'Module resource uploaded.');
    }

    public function storeForLesson(Request $request, CourseLesson $lesson): RedirectResponse
    {
        $validated = $request->validate([
            'resource_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->storeResource(
            course: $lesson->course,
            module: $lesson->module,
            lesson: $lesson,
            file: $validated['resource_file'],
            name: $validated['name'] ?? null,
        );

        return to_route('admin.courses.edit', $lesson->course_id)->with('status', 'Lesson resource uploaded.');
    }

    public function destroy(LessonResource $resource): RedirectResponse
    {
        $courseId = $resource->course_id ?: $resource->lesson?->course_id;
        $disk = config('filesystems.course_resources_disk', 'local');

        if ($resource->storage_key !== '') {
            Storage::disk($disk)->delete($resource->storage_key);
        }

        $resource->delete();

        return to_route('admin.courses.edit', $courseId)->with('status', 'Resource deleted.');
    }

    private function storeResource(
        Course $course,
        ?CourseModule $module,
        ?CourseLesson $lesson,
        \Illuminate\Http\UploadedFile $file,
        ?string $name = null,
    ): void {
        $disk = config('filesystems.course_resources_disk', 'local');
        $scopeFolder = $lesson ? 'lesson' : ($module ? 'module' : 'course');
        $scopeId = $lesson?->id ?? $module?->id ?? $course->id;
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: 'pdf'));
        $storageKey = 'resources/course-'.$course->id.'/'.$scopeFolder.'-'.$scopeId.'/'.Str::uuid().'.'.$extension;
        $storedPath = $file->storeAs(
            dirname($storageKey),
            basename($storageKey),
            $disk
        );

        if (! $storedPath) {
            return;
        }

        $sortQuery = LessonResource::query()->where('course_id', $course->id);
        if ($lesson) {
            $sortQuery->where('lesson_id', $lesson->id);
        } elseif ($module) {
            $sortQuery->where('module_id', $module->id)->whereNull('lesson_id');
        } else {
            $sortQuery->whereNull('module_id')->whereNull('lesson_id');
        }

        LessonResource::query()->create([
            'course_id' => $course->id,
            'module_id' => $module?->id,
            'lesson_id' => $lesson?->id,
            'name' => $name ?: $file->getClientOriginalName(),
            'storage_key' => $storedPath,
            'mime_type' => $file->getClientMimeType() ?: 'application/pdf',
            'size_bytes' => $file->getSize(),
            'sort_order' => ((int) $sortQuery->max('sort_order')) + 1,
        ]);
    }
}
