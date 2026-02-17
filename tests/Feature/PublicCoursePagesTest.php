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

test('catalog and detail include seo meta tags', function (): void {
    $course = Course::factory()->create([
        'title' => 'SEO Course',
        'slug' => 'seo-course',
        'description' => 'A practical course focused on SEO-friendly sales pages.',
        'is_published' => true,
    ]);

    $this->get('/courses')
        ->assertOk()
        ->assertSee('name="description"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('type="application/ld+json"', false)
        ->assertSee('"@type":"ItemList"', false);

    $this->get('/courses/seo-course')
        ->assertOk()
        ->assertSee('<title>SEO Course | '.config('app.name').'</title>', false)
        ->assertSee('property="og:title" content="SEO Course | '.config('app.name').'"', false)
        ->assertSee('property="og:description"', false)
        ->assertSee('type="application/ld+json"', false)
        ->assertSee('"@type":"Course"', false);
});

test('detail page renders intro video when configured', function (): void {
    config()->set('services.cloudflare_stream.signed_urls_enabled', false);
    config()->set('services.cloudflare_stream.iframe_base_url', 'https://iframe.videodelivery.net');

    Course::factory()->create([
        'title' => 'Intro Video Course',
        'slug' => 'intro-video-course',
        'description' => 'Course with intro video.',
        'intro_video_id' => 'intro_video_uid_123',
        'is_published' => true,
    ]);

    $this->get('/courses/intro-video-course')
        ->assertOk()
        ->assertSee('iframe.videodelivery.net/intro_video_uid_123', false)
        ->assertSee('intro video', false);
});

test('detail page renders long description and requirements markdown', function (): void {
    Course::factory()->create([
        'title' => 'Markdown Course',
        'slug' => 'markdown-course',
        'description' => 'Subtitle copy',
        'long_description' => "## Long Details\n\nThis is **important**.",
        'requirements' => "- A computer\n- Focus\n<script>alert('xss')</script>",
        'is_published' => true,
    ]);

    $this->get('/courses/markdown-course')
        ->assertOk()
        ->assertSee('About this course')
        ->assertSee('Requirements')
        ->assertSee('Long Details')
        ->assertSee('important')
        ->assertDontSee("alert('xss')");
});
