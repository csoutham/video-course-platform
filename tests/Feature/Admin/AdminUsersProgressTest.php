<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Entitlement;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non admin users cannot access admin users', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.users.index'))
        ->assertForbidden();

});

test('admin can view users list', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $learner = User::factory()->create(['name' => 'Learner One']);

    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertSeeText('Users')
        ->assertSeeText('Learner One')
        ->assertSee(route('admin.users.show', $learner), false);

});

test('admin can view user course progress summary', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $learner = User::factory()->create([
        'name' => 'Progress User',
        'email' => 'progress@example.com',
    ]);

    $course = Course::factory()->create(['title' => 'Course Progress Test']);
    $module = CourseModule::factory()->create([
        'course_id' => $course->id,
        'sort_order' => 1,
    ]);
    $lessonA = CourseLesson::factory()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'title' => 'Lesson A',
        'sort_order' => 1,
    ]);
    $lessonB = CourseLesson::factory()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'title' => 'Lesson B',
        'sort_order' => 2,
    ]);

    $order = Order::query()->create([
        'user_id' => $learner->id,
        'email' => $learner->email,
        'stripe_checkout_session_id' => 'cs_test_admin_user_progress_1',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    Entitlement::query()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'order_id' => $order->id,
        'status' => 'active',
        'granted_at' => now(),
    ]);

    LessonProgress::query()->create([
        'user_id' => $learner->id,
        'lesson_id' => $lessonA->id,
        'status' => 'completed',
        'playback_position_seconds' => 120,
        'video_duration_seconds' => 120,
        'percent_complete' => 100,
        'started_at' => now()->subMinutes(30),
        'last_viewed_at' => now()->subMinutes(10),
        'completed_at' => now()->subMinutes(10),
    ]);

    LessonProgress::query()->create([
        'user_id' => $learner->id,
        'lesson_id' => $lessonB->id,
        'status' => 'in_progress',
        'playback_position_seconds' => 45,
        'video_duration_seconds' => 120,
        'percent_complete' => 38,
        'started_at' => now()->subMinutes(20),
        'last_viewed_at' => now()->subMinutes(5),
    ]);

    $this->get(route('admin.users.show', $learner))
        ->assertOk()
        ->assertSeeText('Progress User')
        ->assertSeeText('Course Progress Test')
        ->assertSeeText('Completed')
        ->assertSeeText('1')
        ->assertSeeText('In Progress')
        ->assertSeeText('Avg Video Progress')
        ->assertSeeText('69%')
        ->assertSeeText('Lesson Activity Log')
        ->assertSeeText('Lesson A')
        ->assertSeeText('120s')
        ->assertSeeText('Lesson B')
        ->assertSeeText('45s');

});
