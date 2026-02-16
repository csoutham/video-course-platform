<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Imports\Udemy\UdemyCourseImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class UdemyImportsController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.imports.udemy', [
            'preview' => session('udemy_import_preview'),
            'sourceUrl' => old('source_url', (string) $request->query('source_url', '')),
            'sourceHtml' => old('source_html', ''),
            'overwriteMode' => old('overwrite_mode', 'safe_merge'),
        ]);
    }

    public function preview(Request $request, UdemyCourseImportService $importService): RedirectResponse
    {
        $validated = $request->validate([
            'source_url' => ['required', 'url', 'max:2048', 'starts_with:https://www.udemy.com/course/'],
            'source_html' => ['nullable', 'string'],
            'confirm_ownership' => ['accepted'],
        ]);

        try {
            $preview = $importService->preview($validated['source_url'], $validated['source_html'] ?? null);
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['source_url' => $exception->getMessage()]);
        }

        return to_route('admin.imports.udemy.show', ['source_url' => $validated['source_url']])
            ->with('udemy_import_preview', $preview)
            ->withInput();
    }

    public function commit(Request $request, UdemyCourseImportService $importService): RedirectResponse
    {
        $validated = $request->validate([
            'source_url' => ['required', 'url', 'max:2048', 'starts_with:https://www.udemy.com/course/'],
            'source_html' => ['nullable', 'string'],
            'confirm_ownership' => ['accepted'],
            'overwrite_mode' => ['required', Rule::in([
                'safe_merge',
                'force_replace_metadata',
                'force_replace_curriculum',
                'full_replace_imported',
            ])],
        ]);

        try {
            $result = $importService->commit(
                $validated['source_url'],
                $validated['overwrite_mode'],
                $validated['source_html'] ?? null
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['source_url' => $exception->getMessage()]);
        }

        return to_route('admin.courses.edit', $result['course'])
            ->with(
                'status',
                'Udemy import completed (mode: '.$result['mode'].'). Created lessons: '.$result['created_lessons'].', updated lessons: '.$result['updated_lessons'].'.'
            );
    }
}
