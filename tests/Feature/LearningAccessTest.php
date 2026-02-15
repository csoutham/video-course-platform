<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class LearningAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.cloudflare_stream.signed_urls_enabled', false);
    }

    public function test_my_courses_lists_only_entitled_published_courses(): void
    {
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
    }

    public function test_entitled_user_can_access_course_player_default_lesson(): void
    {
        [$user, $course, $lesson] = $this->seedEntitledLesson();

        $this->actingAs($user)
            ->get(route('learn.show', ['course' => $course->slug]))
            ->assertOk()
            ->assertSee($course->title)
            ->assertSee($lesson->title)
            ->assertSee('https://iframe.videodelivery.net/'.$lesson->stream_video_id, false);
    }

    public function test_unentitled_user_cannot_access_course_player(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $this->actingAs($user)
            ->get(route('learn.show', ['course' => $course->slug]))
            ->assertForbidden();
    }

    public function test_signed_stream_url_is_used_when_enabled(): void
    {
        [$user, $course, $lesson] = $this->seedEntitledLesson();

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
    }

    public function test_resource_download_generates_signed_url_and_allows_entitled_user_download(): void
    {
        Storage::fake('local');

        [$user, $course, $lesson] = $this->seedEntitledLesson();

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
    }

    public function test_unentitled_user_cannot_download_resource(): void
    {
        [$user, $course, $lesson] = $this->seedEntitledLesson();

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
    }

    public function test_signed_resource_url_cannot_be_used_by_different_user(): void
    {
        Storage::fake('local');

        [$user, $course, $lesson] = $this->seedEntitledLesson();

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
    }
}
