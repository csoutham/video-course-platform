<?php

namespace App\Services\Certificates;

use App\Models\Course;
use App\Models\CourseCertificate;
use Carbon\CarbonInterface;
use setasign\Fpdi\Fpdi;

class CourseCertificatePdfRenderer
{
    public function render(CourseCertificate $certificate, Course $course, string $templatePath, string $verifyUrl): string
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);

        if ($pageCount < 1) {
            throw new \RuntimeException('Certificate template PDF is empty.');
        }

        $template = $pdf->importPage(1);
        $templateSize = $pdf->getTemplateSize($template);

        $pdf->AddPage($templateSize['orientation'], [$templateSize['width'], $templateSize['height']]);
        $pdf->useTemplate($template);

        $centerX = $templateSize['width'] / 2;
        $pageHeight = $templateSize['height'];
        $baseY = max(50.0, min($pageHeight * 0.46, $pageHeight - 90));

        $this->drawCentered($pdf, $certificate->issued_name, $centerX, $baseY, 26, true);
        $this->drawCentered($pdf, 'has successfully completed', $centerX, $baseY + 12, 13, false);
        $this->drawCentered($pdf, $certificate->issued_course_title, $centerX, $baseY + 24, 18, true);

        $issuedDate = $this->formatIssuedDate($certificate->issued_at);
        $this->drawCentered($pdf, 'Issued '.$issuedDate, $centerX, $baseY + 35, 11, false);

        if ($course->certificate_signatory_name || $course->certificate_signatory_title) {
            $signatory = trim((string) $course->certificate_signatory_name);
            $role = trim((string) $course->certificate_signatory_title);
            $signatureText = trim($signatory.($role !== '' ? ' · '.$role : ''));

            if ($signatureText !== '') {
                $this->drawCentered($pdf, $signatureText, $centerX, $baseY + 46, 10, false);
            }
        }

        $this->drawCentered($pdf, 'Verify: '.$certificate->verification_code, $centerX, $pageHeight - 20, 10, false);
        $this->drawCentered($pdf, $verifyUrl, $centerX, $pageHeight - 14, 8, false);

        return (string) $pdf->Output('S');
    }

    private function drawCentered(Fpdi $pdf, string $text, float $centerX, float $y, float $fontSize, bool $bold): void
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($clean === '') {
            return;
        }

        $pdf->SetFont('Helvetica', $bold ? 'B' : '', $fontSize);
        $pdf->SetTextColor(25, 34, 48);

        $width = $pdf->GetStringWidth($clean);
        $pdf->SetXY(max(8.0, $centerX - ($width / 2)), $y);
        $pdf->Cell($width + 1, 6, utf8_decode($clean), 0, 0, 'L');
    }

    private function formatIssuedDate(?CarbonInterface $issuedAt): string
    {
        return ($issuedAt ?? now())->format('j F Y');
    }
}
