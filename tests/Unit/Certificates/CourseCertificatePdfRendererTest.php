<?php

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\User;
use App\Services\Certificates\CourseCertificatePdfRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pdf renderer returns binary output for valid template', function (): void {
    $user = User::factory()->create(['name' => 'Chris Southam']);
    $course = Course::factory()->create([
        'title' => 'Acting Mastery',
        'certificate_signatory_name' => 'Director',
        'certificate_signatory_title' => 'Lead Coach',
    ]);

    $certificate = CourseCertificate::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'issued_name' => 'Chris Southam',
        'issued_course_title' => 'Acting Mastery',
        'issued_at' => now(),
        'status' => 'active',
    ]);

    $templatePath = tempnam(sys_get_temp_dir(), 'certificate_tpl_');

    expect($templatePath)->not()->toBeFalse();

    $pdf = new \FPDF('P', 'pt', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', 16);
    $pdf->Text(72, 96, 'Template');
    file_put_contents($templatePath, (string) $pdf->Output('S'));

    $renderer = resolve(CourseCertificatePdfRenderer::class);
    $binary = $renderer->render($certificate, $course, $templatePath, 'https://example.test/certificates/verify/ABC123');

    expect($binary)->toBeString();
    expect(strlen($binary))->toBeGreaterThan(100);

    @unlink($templatePath);
});
