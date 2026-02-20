<?php

namespace App\Http\Controllers\Admin\Courses\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Reviews\CourseReviewImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportPreviewController extends Controller
{
    public function __invoke(
        Request $request,
        Course $course,
        CourseReviewImportService $importService,
    ): RedirectResponse {
        abort_unless((bool) config('learning.reviews_enabled'), 404);

        $validated = $request->validate([
            'source_text' => ['required', 'string', 'max:120000'],
        ]);

        $preview = $importService->previewFromText($validated['source_text']);

        session()->put($this->previewSessionKey($course), $preview['rows']);

        return redirect()->to(route('admin.courses.edit', $course).'#reviews')
            ->with('status', 'Parsed '.count($preview['rows']).' row(s).')
            ->with('review_import_errors', $preview['errors'])
            ->withInput(['review_source_text' => $validated['source_text']]);
    }

    private function previewSessionKey(Course $course): string
    {
        return 'course_review_import_preview:'.$course->id;
    }
}
