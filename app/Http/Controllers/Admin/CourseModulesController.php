<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseModulesController extends Controller
{
    public function store(Course $course, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $course->modules()->create([
            'title' => $validated['title'],
            'sort_order' => $validated['sort_order'] ?? ($course->modules()->max('sort_order') + 1),
        ]);

        return to_route('admin.courses.edit', $course)
            ->with('status', 'Module created.');
    }

    public function update(CourseModule $module, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $module->forceFill([
            'title' => $validated['title'],
            'sort_order' => $validated['sort_order'],
        ])->save();

        return to_route('admin.courses.edit', $module->course_id)
            ->with('status', 'Module updated.');
    }

    public function destroy(CourseModule $module): RedirectResponse
    {
        $courseId = $module->course_id;
        $module->delete();

        return to_route('admin.courses.edit', $courseId)
            ->with('status', 'Module deleted.');
    }
}
