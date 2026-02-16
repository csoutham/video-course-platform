<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Entitlement;
use App\Models\LessonResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
            'slug' => 'lesson-1',
            'title' => 'Lesson 1',
            'stream_video_id' => 'sample-stream-video-id',
            'sort_order' => 1,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'stripe_checkout_session_id' => 'cs_entitled_'.$user->id,
            'status' => 'paid',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        Entitlement::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'status' => 'active',
            'granted_at' => now(),
        ]);

        return [$user, $course, $lesson];
    };
});

test('my courses lists only entitled published courses', function (): void {
    $user = User::factory()->create();

    $publishedCourse = Course::factory()->published()->create(['title' => 'Published Access']);
    $draftCourse = Course::factory()->unpublished()->create(['title' => 'Draft Access']);

    $order = Order::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_learning_1',
        'status' => 'paid',
        'subtotal_amount' => 1000,
        'discount_amount' => 0,
        'total_amount' => 1000,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    Entitlement::create([
        'user_id' => $user->id,
        'course_id' => $publishedCourse->id,
        'order_id' => $order->id,
        'status' => 'active',
        'granted_at' => now(),
    ]);

    Entitlement::create([
        'user_id' => $user->id,
        'course_id' => $draftCourse->id,
        'order_id' => $order->id,
        'status' => 'active',
        'granted_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('my-courses.index'))
        ->assertOk()
        ->assertSee('Published Access')
        ->assertDontSee('Draft Access');

});

test('entitled user can access course player default lesson', function (): void {
    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug]))
        ->assertOk()
        ->assertSee($course->title)
        ->assertSee($lesson->title)
        ->assertSee('https://iframe.videodelivery.net/'.$lesson->stream_video_id, false);

});

test('unentitled user cannot access course player', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create();

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug]))
        ->assertForbidden();

});

test('signed stream url is used when enabled', function (): void {
    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    config()->set('services.cloudflare_stream.signed_urls_enabled', true);
    config()->set('services.cloudflare_stream.account_id', 'acct_test_1');
    config()->set('services.cloudflare_stream.api_token', 'token_test_1');
    config()->set('services.cloudflare_stream.customer_code', 'abc123');

    Http::fake([
        'https://api.cloudflare.com/client/v4/accounts/acct_test_1/stream/sample-stream-video-id/token' => Http::response([
            'success' => true,
            'result' => [
                'token' => 'signed_stream_token_1',
            ],
        ], 200),
    ]);

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug]))
        ->assertOk()
        ->assertSee('https://customer-abc123.cloudflarestream.com/signed_stream_token_1/iframe', false);

});

test('resource download generates signed url and allows entitled user download', function (): void {
    Storage::fake('local');

    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    $resource = LessonResource::create([
        'lesson_id' => $lesson->id,
        'name' => 'Lesson Notes.pdf',
        'storage_key' => 'resources/lesson-notes.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 123,
        'sort_order' => 1,
    ]);

    Storage::disk('local')->put('resources/lesson-notes.pdf', 'sample pdf content');

    $downloadResponse = $this->actingAs($user)
        ->get(route('resources.download', $resource));

    $downloadResponse->assertRedirect();

    $signedUrl = $downloadResponse->headers->get('Location');

    $this->actingAs($user)
        ->get($signedUrl)
        ->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'event_type' => 'resource_download_requested',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event_type' => 'resource_stream_served',
        'user_id' => $user->id,
    ]);

});

test('unentitled user cannot download resource', function (): void {
    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    $resource = LessonResource::create([
        'lesson_id' => $lesson->id,
        'name' => 'Restricted.pdf',
        'storage_key' => 'resources/restricted.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 100,
        'sort_order' => 1,
    ]);

    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('resources.download', $resource))
        ->assertForbidden();

});

test('signed resource url cannot be used by different user', function (): void {
    Storage::fake('local');

    [$user, $course, $lesson] = ($this->seedEntitledLesson)();

    $resource = LessonResource::create([
        'lesson_id' => $lesson->id,
        'name' => 'Private.pdf',
        'storage_key' => 'resources/private.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 120,
        'sort_order' => 1,
    ]);

    Storage::disk('local')->put('resources/private.pdf', 'private content');

    $signedUrl = $this->actingAs($user)
        ->get(route('resources.download', $resource))
        ->headers->get('Location');

    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get($signedUrl)
        ->assertForbidden();

});
