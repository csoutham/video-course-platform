<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Entitlement;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_entitled_user_viewing_lesson_creates_in_progress_record(): void
    {
        [$user, $course, $lesson] = $this->seedEntitledLesson();

        $this->actingAs($user)
            ->get(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
            ->assertOk()
            ->assertSee('Mark as complete');

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_entitled_user_can_mark_lesson_as_complete(): void
    {
        [$user, $course, $lesson] = $this->seedEntitledLesson();

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
            ->assertSee('Lesson completed')
            ->assertSee('Completed');
    }

    public function test_unentitled_user_cannot_write_lesson_progress(): void
    {
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
    }

    public function test_default_player_route_uses_next_incomplete_lesson(): void
    {
        [$user, $course, $lessonOne, $lessonTwo] = $this->seedEntitledLessons();

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
    }

    public function test_player_shows_previous_and_next_lesson_links_when_available(): void
    {
        [$user, $course, $lessonOne, $lessonTwo, $lessonThree] = $this->seedEntitledLessons();

        $this->actingAs($user)
            ->get(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lessonTwo->slug]))
            ->assertOk()
            ->assertSee(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lessonOne->slug]), false)
            ->assertSee(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lessonThree->slug]), false)
            ->assertSee('Previous lesson')
            ->assertSee('Next lesson');
    }

    private function seedEntitledLesson(): array
    {
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
    }

    private function seedEntitledLessons(): array
    {
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
    }
}
