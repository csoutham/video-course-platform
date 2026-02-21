<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Entitlement;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use App\Services\Certificates\CourseCertificateEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('learning.certificates_enabled', true);
});

test('eligibility service returns incomplete when not all lessons are completed', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create([
        'certificate_enabled' => true,
        'certificate_template_path' => 'certificates/templates/test.pdf',
    ]);

    $module = CourseModule::factory()->create(['course_id' => $course->id]);

    $lessonOne = CourseLesson::factory()->published()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
    ]);

    CourseLesson::factory()->published()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
    ]);

    $order = Order::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_eligibility_1',
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

    LessonProgress::query()->create([
        'user_id' => $user->id,
        'lesson_id' => $lessonOne->id,
        'status' => 'completed',
        'percent_complete' => 100,
        'started_at' => now(),
        'last_viewed_at' => now(),
        'completed_at' => now(),
    ]);

    $service = resolve(CourseCertificateEligibilityService::class);
    $result = $service->evaluate($user, $course);

    expect($result['eligible'])->toBeFalse();
    expect($result['reason'])->toBe('incomplete_progress');
    expect($result['total_lessons'])->toBe(2);
    expect($result['completed_lessons'])->toBe(1);
});
