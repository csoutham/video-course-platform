<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Entitlement;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('services.cloudflare_stream.signed_urls_enabled', false);
});

function grantCourseAccess(User $user, Course $course): void
{
    $order = Order::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_mobile_'.$user->id,
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
}

function createCourseWithLesson(string $slug = 'mobile-course'): array
{
    $course = Course::factory()->published()->create([
        'slug' => $slug,
        'title' => 'Mobile Course',
    ]);

    $module = CourseModule::factory()->create([
        'course_id' => $course->id,
        'sort_order' => 1,
    ]);

    $lesson = CourseLesson::factory()->published()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'slug' => 'intro-lesson',
        'stream_video_id' => 'stream_test_video_123',
        'duration_seconds' => 300,
        'sort_order' => 1,
    ]);

    return [$course, $module, $lesson];
}

test('mobile auth login returns sanctum token and me endpoint works', function (): void {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $login = $this->postJson('/api/v1/mobile/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone 16',
    ]);

    $login
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'token_type',
            'user' => ['id', 'name', 'email', 'updated_at'],
        ]);

    $token = $login->json('token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/mobile/me')
        ->assertOk()
        ->assertJsonPath('user.email', $user->email);
});

test('mobile auth login returns standardized error on invalid credentials', function (): void {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->postJson('/api/v1/mobile/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'Pixel 9',
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'invalid_credentials')
        ->assertJsonPath('error.message', 'The provided credentials are incorrect.');
});

test('mobile library returns entitled published courses with progress summary', function (): void {
    $user = User::factory()->create();

    [$course, $module, $lesson] = createCourseWithLesson('entitled-course');
    [$unentitledCourse] = createCourseWithLesson('unentitled-course');

    grantCourseAccess($user, $course);

    LessonProgress::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => 'completed',
        'playback_position_seconds' => 300,
        'video_duration_seconds' => 300,
        'percent_complete' => 100,
        'started_at' => now()->subHour(),
        'last_viewed_at' => now()->subMinutes(10),
        'completed_at' => now()->subMinutes(10),
    ]);

    Sanctum::actingAs($user, ['mobile:read']);

    $response = $this->getJson('/api/v1/mobile/library');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'courses')
        ->assertJsonPath('courses.0.slug', $course->slug)
        ->assertJsonPath('courses.0.progress.total_lessons', 1)
        ->assertJsonPath('courses.0.progress.completed_lessons', 1)
        ->assertJsonPath('courses.0.progress.percent_complete', 100);

    expect(collect($response->json('courses'))->pluck('slug'))->not->toContain($unentitledCourse->slug);
});

test('mobile course detail forbids unentitled access with standardized error', function (): void {
    $user = User::factory()->create();
    [$course] = createCourseWithLesson('forbidden-course');

    Sanctum::actingAs($user, ['mobile:read']);

    $this->getJson('/api/v1/mobile/courses/'.$course->slug)
        ->assertForbidden()
        ->assertJsonPath('error.code', 'course_forbidden');
});

test('mobile playback endpoint returns stream url and creates in-progress row', function (): void {
    $user = User::factory()->create();
    [$course, $module, $lesson] = createCourseWithLesson('playback-course');

    grantCourseAccess($user, $course);

    Sanctum::actingAs($user, ['mobile:read']);

    $this->getJson('/api/v1/mobile/courses/'.$course->slug.'/lessons/'.$lesson->slug.'/playback')
        ->assertOk()
        ->assertJsonPath('stream_url', 'https://videodelivery.net/'.$lesson->stream_video_id.'/manifest/video.m3u8')
        ->assertJsonPath('stream.hls_url', 'https://videodelivery.net/'.$lesson->stream_video_id.'/manifest/video.m3u8')
        ->assertJsonPath('stream.iframe_url', 'https://iframe.videodelivery.net/'.$lesson->stream_video_id)
        ->assertJsonPath('stream.player', 'native')
        ->assertJsonPath('lesson.slug', $lesson->slug)
        ->assertJsonPath('progress.status', 'in_progress');

    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => 'in_progress',
    ]);
});

test('mobile progress endpoint auto completes lesson when threshold reached', function (): void {
    config()->set('learning.video_autocomplete_percent', 90);

    $user = User::factory()->create();
    [$course, $module, $lesson] = createCourseWithLesson('progress-course');

    grantCourseAccess($user, $course);

    Sanctum::actingAs($user, ['mobile:progress:write']);

    $this->postJson('/api/v1/mobile/courses/'.$course->slug.'/lessons/'.$lesson->slug.'/progress', [
        'position_seconds' => 270,
        'duration_seconds' => 300,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('percent_complete', 100)
        ->assertJsonPath('playback_position_seconds', 270);
});

test('mobile resource endpoint issues signed url and signed file endpoint serves entitled user', function (): void {
    Storage::fake('local');
    config()->set('filesystems.course_resources_disk', 'local');

    $user = User::factory()->create();
    [$course, $module, $lesson] = createCourseWithLesson('resource-course');

    grantCourseAccess($user, $course);

    $resource = LessonResource::factory()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'lesson_id' => $lesson->id,
        'name' => 'Resource.pdf',
        'storage_key' => 'resources/resource.pdf',
    ]);

    Storage::disk('local')->put('resources/resource.pdf', 'resource-content');

    Sanctum::actingAs($user, ['mobile:read']);

    $signedResponse = $this->getJson('/api/v1/mobile/resources/'.$resource->id)
        ->assertOk()
        ->assertJsonPath('resource.id', $resource->id);

    $signedUrl = $signedResponse->json('resource.url');

    $this->get($signedUrl)
        ->assertOk();
});

test('mobile receipts endpoint returns current users eligible stripe receipts', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $eligibleOrder = Order::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_receipt_mobile_1',
        'stripe_receipt_url' => 'https://pay.stripe.com/receipts/mobile_receipt_1',
        'status' => 'paid',
        'subtotal_amount' => 1000,
        'discount_amount' => 0,
        'total_amount' => 1000,
        'currency' => 'usd',
        'paid_at' => now()->subDay(),
    ]);

    Order::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'free_01kabcxyzxyzxyzxyzxyzxyz',
        'status' => 'paid',
        'subtotal_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 0,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    Order::create([
        'user_id' => $otherUser->id,
        'email' => $otherUser->email,
        'stripe_checkout_session_id' => 'cs_receipt_mobile_other',
        'stripe_receipt_url' => 'https://pay.stripe.com/receipts/mobile_receipt_other',
        'status' => 'paid',
        'subtotal_amount' => 1200,
        'discount_amount' => 0,
        'total_amount' => 1200,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    Sanctum::actingAs($user, ['mobile:read']);

    $this->getJson('/api/v1/mobile/receipts')
        ->assertOk()
        ->assertJsonCount(1, 'receipts')
        ->assertJsonPath('receipts.0.order_public_id', $eligibleOrder->public_id)
        ->assertJsonPath('receipts.0.receipt_url', 'https://pay.stripe.com/receipts/mobile_receipt_1');
});
