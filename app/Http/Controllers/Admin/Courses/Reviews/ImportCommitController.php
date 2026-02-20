<?php

namespace App\Http\Controllers\Admin\Courses\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Reviews\CourseRatingAggregateService;
use App\Services\Reviews\CourseReviewImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportCommitController extends Controller
{
    public function __invoke(
        Request $request,
        Course $course,
        CourseReviewImportService $importService,
        CourseRatingAggregateService $aggregateService,
    ): RedirectResponse {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.rating' => ['required', 'integer', 'min:1', 'max:5'],
            'rows.*.reviewer_name' => ['required', 'string', 'max:120'],
            'rows.*.title' => ['nullable', 'string', 'max:120'],
            'rows.*.body' => ['nullable', 'string', 'max:2000'],
            'rows.*.original_reviewed_at' => ['nullable', 'date'],
        ]);

        $created = DB::transaction(function () use ($importService, $aggregateService, $course, $validated, $request): int {
            $createdRows = $importService->importRows($course, $request->user(), $validated['rows']);
            $aggregateService->refreshForCourse($course);

            return $createdRows;
        });

        session()->forget($this->previewSessionKey($course));

        return redirect()->to(route('admin.courses.edit', $course).'#reviews')
            ->with('status', "{$created} imported review(s) saved.");
    }

    private function previewSessionKey(Course $course): string
    {
        return 'course_review_import_preview:'.$course->id;
    }
}
