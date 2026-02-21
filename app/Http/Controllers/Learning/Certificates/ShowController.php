<?php

namespace App\Http\Controllers\Learning\Certificates;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Certificates\CourseCertificateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowController extends Controller
{
    public function __invoke(Request $request, Course $course, CourseCertificateService $certificateService): Response
    {
        abort_unless((bool) config('learning.certificates_enabled'), 404);

        try {
            $certificate = $certificateService->ensureIssuedForCourse($request->user(), $course);
        } catch (\RuntimeException) {
            abort(403, 'Certificate is not available yet.');
        }

        abort_if($certificate->status !== 'active', 403, 'Certificate is not available.');

        try {
            $pdf = $certificateService->renderPdf($certificate->fresh('course'));
        } catch (\RuntimeException) {
            abort(422, 'Certificate template is not available.');
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.strtolower($certificate->public_id).'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
