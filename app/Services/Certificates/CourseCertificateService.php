<?php

namespace App\Services\Certificates;

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\User;
use App\Services\Learning\CourseAccessService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CourseCertificateService
{
    public function __construct(
        private readonly CourseCertificateEligibilityService $eligibilityService,
        private readonly CourseCertificatePdfRenderer $pdfRenderer,
        private readonly CourseAccessService $accessService,
    ) {
    }

    /**
     * @return array{eligible: bool, reason: string|null, total_lessons: int, completed_lessons: int, certificate: CourseCertificate|null}
     */
    public function eligibilityWithCertificate(User $user, Course $course): array
    {
        $eligibility = $this->eligibilityService->evaluate($user, $course);

        $certificate = CourseCertificate::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        return $eligibility + [
            'certificate' => $certificate,
        ];
    }

    public function ensureIssuedForCourse(User $user, Course $course): CourseCertificate
    {
        $eligibility = $this->eligibilityService->evaluate($user, $course);

        if (! $eligibility['eligible']) {
            throw new \RuntimeException('Course certificate is not available yet.');
        }

        [$orderId, $subscriptionId] = $this->resolveSourceReferences($user, $course);
        $issuedName = $this->resolveIssuedName($user);
        $templateVersion = $this->resolveTemplateVersion($course->certificate_template_path);

        $certificate = CourseCertificate::query()->firstOrNew([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        if (! $certificate->exists) {
            $certificate->forceFill([
                'order_id' => $orderId,
                'subscription_id' => $subscriptionId,
                'issued_name' => $issuedName,
                'issued_course_title' => $course->title,
                'issued_at' => now(),
                'status' => 'active',
                'revoked_at' => null,
                'revoke_reason' => null,
                'template_version' => $templateVersion,
            ])->save();

            return $certificate;
        }

        if ($certificate->status === 'revoked' && $this->accessService->userHasActiveCourseEntitlement($user, $course)) {
            $certificate->forceFill([
                'order_id' => $orderId,
                'subscription_id' => $subscriptionId,
                'issued_name' => $issuedName,
                'issued_course_title' => $course->title,
                'issued_at' => now(),
                'status' => 'active',
                'revoked_at' => null,
                'revoke_reason' => null,
                'template_version' => $templateVersion,
            ])->save();
        }

        return $certificate;
    }

    public function renderPdf(CourseCertificate $certificate): string
    {
        $course = $certificate->course;

        if (! $course || ! $course->certificate_template_path) {
            throw new \RuntimeException('Course certificate template is not configured.');
        }

        $diskName = (string) config('filesystems.image_upload_disk', 'public');
        $disk = Storage::disk($diskName);

        if (! $disk->exists($course->certificate_template_path)) {
            throw new \RuntimeException('Certificate template file is missing.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'certtpl_');

        if ($tempFile === false) {
            throw new \RuntimeException('Unable to create temporary certificate file.');
        }

        $stream = $disk->readStream($course->certificate_template_path);

        if (! is_resource($stream)) {
            @unlink($tempFile);
            throw new \RuntimeException('Unable to read certificate template.');
        }

        $target = fopen($tempFile, 'wb');

        if (! is_resource($target)) {
            fclose($stream);
            @unlink($tempFile);
            throw new \RuntimeException('Unable to prepare certificate template stream.');
        }

        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);

        try {
            $verifyUrl = URL::route('certificates.verify', ['code' => $certificate->verification_code]);
            return $this->pdfRenderer->render($certificate, $course, $tempFile, $verifyUrl);
        } finally {
            @unlink($tempFile);
        }
    }

    public function revokeForOrder(int $orderId, string $reason = 'order_refunded'): int
    {
        return CourseCertificate::query()
            ->where('order_id', $orderId)
            ->where('status', 'active')
            ->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revoke_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    private function resolveIssuedName(User $user): string
    {
        $name = trim((string) $user->name);

        if ($name !== '') {
            return Str::limit($name, 160, '');
        }

        $emailName = trim((string) Str::before((string) $user->email, '@'));

        return Str::limit($emailName !== '' ? $emailName : 'Learner', 160, '');
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveSourceReferences(User $user, Course $course): array
    {
        $entitlement = $user->entitlements()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if ($entitlement?->order_id) {
            return [(int) $entitlement->order_id, null];
        }

        $subscriptionId = null;

        if ((bool) config('learning.subscriptions_enabled') && ! $course->is_subscription_excluded) {
            $subscription = $user->subscriptions()
                ->where(function ($query): void {
                    $query
                        ->whereIn('status', ['active', 'trialing'])
                        ->orWhere(function ($canceled): void {
                            $canceled->where('status', 'canceled')->where('current_period_end', '>', now());
                        });
                })
                ->latest('id')
                ->first();

            $subscriptionId = $subscription?->id;
        }

        return [null, $subscriptionId ? (int) $subscriptionId : null];
    }

    private function resolveTemplateVersion(?string $templatePath): ?string
    {
        if (! $templatePath) {
            return null;
        }

        $diskName = (string) config('filesystems.image_upload_disk', 'public');
        $disk = Storage::disk($diskName);

        if (! $disk->exists($templatePath)) {
            return null;
        }

        $timestamp = $disk->lastModified($templatePath);

        return sha1($templatePath.'|'.$timestamp);
    }
}
