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

beforeEach(function (): void {
    config()->set('services.cloudflare_stream.signed_urls_enabled', false);
    $this->seedEntitledLesson = function (): array {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $module = CourseModule::factory()->create([
            'course_id' => $course->id,
            'sort_order' => 1,
        ]);

        $lesson = CourseLesson::factory()->published()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'slug' => 'lesson-progress-1',
            'title' => 'Lesson Progress 1',
            'stream_video_id' => 'sample-stream-video-id',
            'sort_order' => 1,
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'stripe_checkout_session_id' => 'cs_progress_'.$user->id,
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

        return [$user, $course, $lesson];
    };
    $this->seedEntitledLessons = function (): array {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $module = CourseModule::factory()->create([
            'course_id' => $course->id,
            'sort_order' => 1,
        ]);

        $lessonOne = CourseLesson::factory()->published()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'slug' => 'lesson-one',
            'title' => 'Lesson One',
            'sort_order' => 1,
        ]);

        $lessonTwo = CourseLesson::factory()->published()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'slug' => 'lesson-two',
            'title' => 'Lesson Two',
            'sort_order' => 2,
        ]);

        $lessonThree = CourseLesson::factory()->published()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'slug' => 'lesson-three',
            'title' => 'Lesson Three',
            'sort_order' => 3,
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'stripe_checkout_session_id' => 'cs_progress_multiple_'.$user->id,
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

        return [$user, $course, $lessonOne, $lessonTwo, $lessonThree];
    };
});

test('entitled user viewing lesson creates in progress record', function (): void {
    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
        ->assertOk()
        ->assertSee('Mark as complete');

    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => 'in_progress',
    ]);

});

test('entitled user can mark lesson as complete', function (): void {
    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    $this->actingAs($user)
        ->post(route('learn.progress.complete', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
        ->assertRedirect(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]));

    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => 'completed',
    ]);

    $progress = LessonProgress::query()
        ->where('user_id', $user->id)
        ->where('lesson_id', $lesson->id)
        ->first();

    $this->assertNotNull($progress?->completed_at);

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
        ->assertOk()
        ->assertSee('Mark as incomplete');

});

test('entitled user can unmark lesson as complete', function (): void {
    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    LessonProgress::query()->create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => 'completed',
        'percent_complete' => 100,
        'started_at' => now()->subHour(),
        'last_viewed_at' => now()->subHour(),
        'completed_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->post(route('learn.progress.complete', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
        ->assertRedirect(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]));

    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => 'in_progress',
        'percent_complete' => 99,
    ]);

    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'completed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
        ->assertOk()
        ->assertSee('Mark as complete');

});

test('unentitled user cannot write lesson progress', function (): void {
    $course = Course::factory()->published()->create();

    $module = CourseModule::factory()->create([
        'course_id' => $course->id,
        'sort_order' => 1,
    ]);

    $lesson = CourseLesson::factory()->published()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'slug' => 'restricted-lesson',
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('learn.progress.complete', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
        ->assertForbidden();

    $this->assertDatabaseMissing('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
    ]);

});

test('entitled user can store video progress heartbeat', function (): void {
    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    $this->actingAs($user)
        ->postJson(route('learn.progress.video', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]), [
            'position_seconds' => 42,
            'duration_seconds' => 120,
        ])
        ->assertOk()
        ->assertJson([
            'status' => 'in_progress',
            'percent_complete' => 35,
        ]);

    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => 'in_progress',
        'playback_position_seconds' => 42,
        'video_duration_seconds' => 120,
        'percent_complete' => 35,
    ]);

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
        ->assertOk()
        ->assertSee('resumeSeconds', false);

});

test('video progress auto completes when threshold reached', function (): void {
    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    config()->set('learning.video_autocomplete_percent', 90);

    $this->actingAs($user)
        ->postJson(route('learn.progress.video', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]), [
            'position_seconds' => 95,
            'duration_seconds' => 100,
        ])
        ->assertOk()
        ->assertJson([
            'status' => 'completed',
            'percent_complete' => 100,
        ]);

    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => 'completed',
        'percent_complete' => 100,
    ]);

    $this->assertNotNull(LessonProgress::query()->firstWhere('lesson_id', $lesson->id)?->completed_at);

});

test('unentitled user cannot store video progress heartbeat', function (): void {
    $course = Course::factory()->published()->create();

    $module = CourseModule::factory()->create([
        'course_id' => $course->id,
        'sort_order' => 1,
    ]);

    $lesson = CourseLesson::factory()->published()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'slug' => 'video-restricted-lesson',
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('learn.progress.video', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]), [
            'position_seconds' => 10,
            'duration_seconds' => 100,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
    ]);

});

test('default player route uses next incomplete lesson', function (): void {
    [$user, $course, $lessonOne, $lessonTwo] = ($this->seedEntitledLessons)();

    LessonProgress::query()->create([
        'user_id' => $user->id,
        'lesson_id' => $lessonOne->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'last_viewed_at' => now()->subDay(),
        'completed_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug]))
        ->assertOk()
        ->assertSee(route('learn.progress.complete', ['course' => $course->slug, 'lessonSlug' => $lessonTwo->slug]), false);

});

test('player shows previous and next lesson links when available', function (): void {
    [$user, $course, $lessonOne, $lessonTwo, $lessonThree] = ($this->seedEntitledLessons)();

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lessonTwo->slug]))
        ->assertOk()
        ->assertSee(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lessonOne->slug]), false)
        ->assertSee(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lessonThree->slug]), false)
        ->assertSee('Previous lesson')
        ->assertSee('Next lesson');

});
