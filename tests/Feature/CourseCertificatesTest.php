<?php

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Entitlement;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('learning.certificates_enabled', true);
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    config()->set('services.cloudflare_stream.signed_urls_enabled', false);
    config()->set('filesystems.image_upload_disk', 'public');

    Storage::fake('public');

    $this->generateSignatureHeader = function (string $payload, string $secret): string {
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    };

    $this->storeTemplate = function (string $path = 'certificates/templates/default.pdf'): string {
        $pdf = new \FPDF('P', 'pt', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 16);
        $pdf->Text(72, 96, 'Certificate Template');

        Storage::disk('public')->put($path, (string) $pdf->Output('S'));

        return $path;
    };

    $this->seedCourseWithProgress = function (int $completedLessons, int $totalLessons = 2): array {
        $user = User::factory()->create([
            'name' => 'Chris Southam',
        ]);

        $course = Course::factory()->published()->create([
            'certificate_enabled' => true,
            'certificate_template_path' => ($this->storeTemplate)(),
            'certificate_signatory_name' => 'Course Director',
            'certificate_signatory_title' => 'Lead Instructor',
        ]);

        $module = CourseModule::factory()->create([
            'course_id' => $course->id,
            'sort_order' => 1,
        ]);

        $lessons = collect();

        for ($i = 1; $i <= $totalLessons; $i++) {
            $lessons->push(CourseLesson::factory()->published()->create([
                'course_id' => $course->id,
                'module_id' => $module->id,
                'slug' => 'certificate-lesson-'.$i,
                'sort_order' => $i,
            ]));
        }

        $order = Order::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'stripe_checkout_session_id' => 'cs_cert_'.$user->id,
            'stripe_payment_intent_id' => 'pi_cert_'.$user->id,
            'status' => 'paid',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        Entitlement::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'status' => 'active',
            'granted_at' => now(),
        ]);

        $lessons->take($completedLessons)->each(function (CourseLesson $lesson) use ($user): void {
            LessonProgress::query()->create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'status' => 'completed',
                'percent_complete' => 100,
                'started_at' => now()->subDay(),
                'last_viewed_at' => now()->subHour(),
                'completed_at' => now()->subHour(),
            ]);
        });

        return [$user, $course, $order];
    };
});

test('eligible learner can download certificate and issuance is idempotent', function (): void {
    [$user, $course] = ($this->seedCourseWithProgress)(2, 2);

    $response = $this->actingAs($user)
        ->get(route('certificates.show', ['course' => $course->slug]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');

    $this->assertDatabaseCount('course_certificates', 1);

    $firstCertificate = CourseCertificate::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('certificates.show', ['course' => $course->slug]))
        ->assertOk();

    $this->assertDatabaseCount('course_certificates', 1);
    $this->assertSame($firstCertificate->id, CourseCertificate::query()->firstOrFail()->id);
});

test('ineligible learner cannot download certificate before full completion', function (): void {
    [$user, $course] = ($this->seedCourseWithProgress)(1, 2);

    $this->actingAs($user)
        ->get(route('certificates.show', ['course' => $course->slug]))
        ->assertForbidden();

    $this->assertDatabaseCount('course_certificates', 0);
});

test('unentitled learner cannot download certificate', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create([
        'certificate_enabled' => true,
        'certificate_template_path' => ($this->storeTemplate)('certificates/templates/public.pdf'),
    ]);

    $this->actingAs($user)
        ->get(route('certificates.show', ['course' => $course->slug]))
        ->assertForbidden();
});

test('public verify page shows active certificate', function (): void {
    [$user, $course] = ($this->seedCourseWithProgress)(2, 2);

    $this->actingAs($user)
        ->get(route('certificates.show', ['course' => $course->slug]))
        ->assertOk();

    $certificate = CourseCertificate::query()->firstOrFail();

    $this->get(route('certificates.verify', ['code' => $certificate->verification_code]))
        ->assertOk()
        ->assertSee('Certificate Verified')
        ->assertSee($certificate->issued_name)
        ->assertSee($certificate->issued_course_title);
});

test('full refund webhook revokes certificate', function (): void {
    [$user, $course, $order] = ($this->seedCourseWithProgress)(2, 2);

    $this->actingAs($user)
        ->get(route('certificates.show', ['course' => $course->slug]))
        ->assertOk();

    $certificate = CourseCertificate::query()->firstOrFail();

    $payload = [
        'id' => 'evt_certificate_refund_full_1',
        'object' => 'event',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_certificate_refund_full_1',
                'object' => 'charge',
                'amount' => 1000,
                'amount_refunded' => 1000,
                'refunded' => true,
                'payment_intent' => $order->stripe_payment_intent_id,
                'metadata' => [
                    'checkout_session_id' => $order->stripe_checkout_session_id,
                ],
            ],
        ],
    ];

    $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

    $this->withHeaders([
        'Stripe-Signature' => $signature,
    ])->postJson(route('webhooks.stripe'), $payload)->assertOk();

    $this->assertDatabaseHas('course_certificates', [
        'id' => $certificate->id,
        'status' => 'revoked',
    ]);

    $this->actingAs($user)
        ->get(route('certificates.show', ['course' => $course->slug]))
        ->assertForbidden();
});

test('partial refund webhook does not revoke certificate', function (): void {
    [$user, $course, $order] = ($this->seedCourseWithProgress)(2, 2);

    $this->actingAs($user)
        ->get(route('certificates.show', ['course' => $course->slug]))
        ->assertOk();

    $certificate = CourseCertificate::query()->firstOrFail();

    $payload = [
        'id' => 'evt_certificate_refund_partial_1',
        'object' => 'event',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_certificate_refund_partial_1',
                'object' => 'charge',
                'amount' => 1000,
                'amount_refunded' => 500,
                'refunded' => false,
                'payment_intent' => $order->stripe_payment_intent_id,
                'metadata' => [
                    'checkout_session_id' => $order->stripe_checkout_session_id,
                ],
            ],
        ],
    ];

    $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

    $this->withHeaders([
        'Stripe-Signature' => $signature,
    ])->postJson(route('webhooks.stripe'), $payload)->assertOk();

    $this->assertDatabaseHas('course_certificates', [
        'id' => $certificate->id,
        'status' => 'active',
    ]);
});
