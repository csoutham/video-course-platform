<?php

namespace App\Http\Controllers\Admin\Courses\Certificate;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    public function __invoke(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'certificate_enabled' => ['nullable', 'boolean'],
            'certificate_template_pdf' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:10240'],
            'certificate_signatory_name' => ['nullable', 'string', 'max:120'],
            'certificate_signatory_title' => ['nullable', 'string', 'max:120'],
        ]);

        $templatePath = $course->certificate_template_path;
        $diskName = (string) config('filesystems.image_upload_disk', 'public');

        if ($request->hasFile('certificate_template_pdf')) {
            $templatePath = $request->file('certificate_template_pdf')?->store('certificates/templates', $diskName);

            if ($course->certificate_template_path && $course->certificate_template_path !== $templatePath) {
                Storage::disk($diskName)->delete($course->certificate_template_path);
            }
        }

        if ((bool) ($validated['certificate_enabled'] ?? false) && ! $templatePath) {
            return back()
                ->withInput()
                ->withErrors(['certificate_template_pdf' => 'A template PDF is required when certificates are enabled.']);
        }

        $course->forceFill([
            'certificate_enabled' => (bool) ($validated['certificate_enabled'] ?? false),
            'certificate_template_path' => $templatePath,
            'certificate_signatory_name' => $this->nullableTrim($validated['certificate_signatory_name'] ?? null),
            'certificate_signatory_title' => $this->nullableTrim($validated['certificate_signatory_title'] ?? null),
        ])->save();

        return to_route('admin.courses.certificate.edit', $course)
            ->with('status', 'Certificate settings updated.');
    }

    private function nullableTrim(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
