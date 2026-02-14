<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_completed_webhook_creates_order_and_entitlement(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $payload = [
            'id' => 'evt_checkout_completed_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'currency' => 'usd',
                    'amount_subtotal' => 9900,
                    'amount_total' => 9900,
                    'customer' => 'cus_test_123',
                    'customer_email' => $user->email,
                    'metadata' => [
                        'course_id' => (string) $course->id,
                        'user_id' => (string) $user->id,
                        'customer_email' => $user->email,
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        $signature = $this->generateSignatureHeader($jsonPayload, 'whsec_test');

        $this->withHeaders([
            'Stripe-Signature' => $signature,
        ])->postJson(route('webhooks.stripe'), $payload)
            ->assertOk();

        $this->assertDatabaseHas('stripe_events', [
            'stripe_event_id' => 'evt_checkout_completed_1',
            'event_type' => 'checkout.session.completed',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'stripe_webhook_processed',
        ]);

        $order = Order::query()->firstWhere('stripe_checkout_session_id', 'cs_test_123');
        $this->assertNotNull($order);
        $this->assertSame('paid', $order->status);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'course_id' => $course->id,
        ]);

        $this->assertDatabaseHas('entitlements', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('purchase_claim_tokens', [
            'order_id' => $order->id,
        ]);
    }

    public function test_repeated_event_is_idempotent(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $payload = [
            'id' => 'evt_checkout_idempotent',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_idempotent',
                    'object' => 'checkout.session',
                    'currency' => 'usd',
                    'amount_subtotal' => 9900,
                    'amount_total' => 9900,
                    'customer' => 'cus_test_idempotent',
                    'customer_email' => $user->email,
                    'metadata' => [
                        'course_id' => (string) $course->id,
                        'user_id' => (string) $user->id,
                        'customer_email' => $user->email,
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $this->generateSignatureHeader($jsonPayload, 'whsec_test');

        $headers = ['Stripe-Signature' => $signature];

        $this->withHeaders($headers)->postJson(route('webhooks.stripe'), $payload)->assertOk();
        $this->withHeaders($headers)->postJson(route('webhooks.stripe'), $payload)->assertOk();

        $this->assertSame(1, StripeEvent::query()->where('stripe_event_id', 'evt_checkout_idempotent')->count());

        $order = Order::query()->firstWhere('stripe_checkout_session_id', 'cs_test_idempotent');

        $this->assertNotNull($order);
        $this->assertSame(1, OrderItem::query()->where('order_id', $order->id)->count());
        $this->assertSame(1, Entitlement::query()->where('order_id', $order->id)->count());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $payload = [
            'id' => 'evt_invalid_sig',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => ['id' => 'cs_test_invalid'],
            ],
        ];

        $this->withHeaders([
            'Stripe-Signature' => 't=1,v1=invalid',
        ])->postJson(route('webhooks.stripe'), $payload)
            ->assertStatus(400);
    }

    public function test_guest_checkout_creates_claim_token_without_entitlement(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $course = Course::factory()->published()->create();

        $payload = [
            'id' => 'evt_guest_checkout_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_guest_1',
                    'object' => 'checkout.session',
                    'currency' => 'usd',
                    'amount_subtotal' => 9900,
                    'amount_total' => 9900,
                    'customer_email' => 'guestbuyer@example.com',
                    'metadata' => [
                        'course_id' => (string) $course->id,
                        'customer_email' => 'guestbuyer@example.com',
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $this->generateSignatureHeader($jsonPayload, 'whsec_test');

        $this->withHeaders([
            'Stripe-Signature' => $signature,
        ])->postJson(route('webhooks.stripe'), $payload)->assertOk();

        $order = Order::query()->firstWhere('stripe_checkout_session_id', 'cs_test_guest_1');
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);

        $this->assertDatabaseHas('purchase_claim_tokens', [
            'order_id' => $order->id,
        ]);

        $this->assertSame(0, Entitlement::query()->where('order_id', $order->id)->count());
    }

    public function test_refund_webhook_revokes_entitlements_for_order(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'stripe_checkout_session_id' => 'cs_test_refund',
            'status' => 'paid',
            'subtotal_amount' => 9900,
            'discount_amount' => 0,
            'total_amount' => 9900,
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

        $payload = [
            'id' => 'evt_refund_1',
            'object' => 'event',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_refund',
                    'object' => 'charge',
                    'metadata' => [
                        'checkout_session_id' => 'cs_test_refund',
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $this->generateSignatureHeader($jsonPayload, 'whsec_test');

        $this->withHeaders([
            'Stripe-Signature' => $signature,
        ])->postJson(route('webhooks.stripe'), $payload)->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
        ]);

        $this->assertDatabaseHas('entitlements', [
            'order_id' => $order->id,
            'status' => 'revoked',
        ]);
    }

    private function generateSignatureHeader(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
