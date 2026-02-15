<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCoursePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_published_courses_only(): void
    {
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
    }

    public function test_catalog_shows_empty_state_when_no_published_courses_exist(): void
    {
        Course::factory()->unpublished()->create();

        $this->get('/courses')
            ->assertOk()
            ->assertSee('No published courses yet');
    }

    public function test_detail_page_shows_published_course_and_published_lessons_only(): void
    {
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
    }

    public function test_detail_page_returns_not_found_for_unknown_or_unpublished_course(): void
    {
        Course::factory()->unpublished()->create([
            'slug' => 'internal-draft',
        ]);

        $this->get('/courses/missing-course')->assertNotFound();
        $this->get('/courses/internal-draft')->assertNotFound();
    }

    public function test_catalog_links_entitled_logged_in_user_directly_to_learning(): void
    {
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
    }
}
