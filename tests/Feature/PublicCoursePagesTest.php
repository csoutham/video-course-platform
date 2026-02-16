<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('catalog shows published courses only', function (): void {
    Course::factory()->create([
        'title' => 'Published Course',
        'slug' => 'published-course',
        'is_published' => true,
    ]);

    Course::factory()->unpublished()->create([
        'title' => 'Draft Course',
        'slug' => 'draft-course',
    ]);

    $response = $this->get('/courses');

    $response
        ->assertOk()
        ->assertSee('Published Course')
        ->assertDontSee('Draft Course');

});

test('catalog shows empty state when no published courses exist', function (): void {
    Course::factory()->unpublished()->create();

    $this->get('/courses')
        ->assertOk()
        ->assertSee('No published courses yet');

});

test('detail page shows published course and published lessons only', function (): void {
    $course = Course::factory()->create([
        'title' => 'Laravel Foundations',
        'slug' => 'laravel-foundations',
        'is_published' => true,
    ]);

    $module = CourseModule::factory()->create([
        'course_id' => $course->id,
        'title' => 'Module 1',
    ]);

    CourseLesson::factory()->published()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'title' => 'Published Lesson',
        'slug' => 'published-lesson',
    ]);

    CourseLesson::factory()->unpublished()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'title' => 'Draft Lesson',
        'slug' => 'draft-lesson',
    ]);

    $this->get('/courses/laravel-foundations')
        ->assertOk()
        ->assertSee('Laravel Foundations')
        ->assertSee('Published Lesson')
        ->assertDontSee('Draft Lesson');

});

test('detail page returns not found for unknown or unpublished course', function (): void {
    Course::factory()->unpublished()->create([
        'slug' => 'internal-draft',
    ]);

    $this->get('/courses/missing-course')->assertNotFound();
    $this->get('/courses/internal-draft')->assertNotFound();

});

test('catalog links entitled logged in user directly to learning', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create([
        'slug' => 'owned-course',
        'title' => 'Owned Course',
    ]);

    $order = Order::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_catalog_owned_1',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
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

    $this->actingAs($user)
        ->get('/courses')
        ->assertOk()
        ->assertSee('Continue learning')
        ->assertSee(route('learn.show', ['course' => $course->slug]), false);

});
