<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Order;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OperationsCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_entitlement_grant_and_revoke_commands_work(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $order = Order::create([
            'email' => $user->email,
            'stripe_checkout_session_id' => 'cs_ops_1',
            'status' => 'paid',
            'subtotal_amount' => 9900,
            'discount_amount' => 0,
            'total_amount' => 9900,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        Artisan::call('videocourses:entitlement-grant', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('entitlements', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'status' => 'active',
        ]);

        Artisan::call('videocourses:entitlement-revoke', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $this->assertDatabaseHas('entitlements', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'revoked',
        ]);
    }

    public function test_stripe_reprocess_command_replays_stored_event(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $payload = [
            'id' => 'evt_ops_reprocess_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_ops_reprocess_1',
                    'object' => 'checkout.session',
                    'currency' => 'usd',
                    'amount_subtotal' => 9900,
                    'amount_total' => 9900,
                    'customer' => 'cus_ops_1',
                    'customer_email' => $user->email,
                    'metadata' => [
                        'course_id' => (string) $course->id,
                        'user_id' => (string) $user->id,
                        'customer_email' => $user->email,
                    ],
                ],
            ],
        ];

        StripeEvent::create([
            'stripe_event_id' => 'evt_ops_reprocess_1',
            'event_type' => 'checkout.session.completed',
            'payload_json' => $payload,
            'processed_at' => null,
        ]);

        Artisan::call('videocourses:stripe-reprocess', [
            'event_id' => 'evt_ops_reprocess_1',
        ]);

        $order = Order::query()->firstWhere('stripe_checkout_session_id', 'cs_ops_reprocess_1');

        $this->assertNotNull($order);
        $this->assertSame('paid', $order->status);

        $this->assertDatabaseHas('entitlements', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'stripe_event_reprocessed',
        ]);
    }

    public function test_stream_sync_durations_command_updates_lesson_lengths(): void
    {
        config()->set('services.cloudflare_stream.account_id', 'acct_test_1');
        config()->set('services.cloudflare_stream.api_token', 'token_test_1');

        $course = Course::factory()->published()->create();
        $module = CourseModule::factory()->create([
            'course_id' => $course->id,
        ]);

        $lesson = CourseLesson::factory()->published()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'stream_video_id' => 'video_sync_1',
            'duration_seconds' => null,
        ]);

        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/acct_test_1/stream/video_sync_1' => Http::response([
                'success' => true,
                'result' => [
                    'duration' => 132.4,
                ],
            ], 200),
        ]);

        Artisan::call('videocourses:stream-sync-durations');

        $lesson->refresh();

        $this->assertSame(132, $lesson->duration_seconds);
    }
}
