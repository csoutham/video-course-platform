<?php

use App\Mail\GiftDeliveryMail;
use App\Mail\GiftPurchaseConfirmationMail;
use App\Mail\PurchaseReceiptMail;
use App\Models\Course;
use App\Models\Entitlement;
use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PurchaseClaimToken;
use App\Models\StripeEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->generateSignatureHeader = function (string $payload, string $secret): string {
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    };
});

test('checkout completed webhook creates order and entitlement', function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    config()->set('services.kit.enabled', true);
    config()->set('services.kit.api_key', 'kit_test_key');
    config()->set('services.kit.purchaser_tag_ids', '101');
    Mail::fake();
    Http::fake([
        'https://api.kit.com/v4/subscribers' => Http::response(['id' => 'sub_1'], 201),
        'https://api.kit.com/v4/tags/*/subscribers' => Http::response(['ok' => true], 201),
    ]);

    $user = User::factory()->create();
    $course = Course::factory()->published()->create([
        'kit_tag_id' => 202,
    ]);

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

    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

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

    Mail::assertSent(PurchaseReceiptMail::class, fn (PurchaseReceiptMail $mail): bool => $mail->hasTo($user->email)
        && $mail->claimUrl === null);

    Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.kit.com/v4/subscribers'
        && $request['email_address'] === $user->email);

    Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.kit.com/v4/tags/101/subscribers'
        && $request['email_address'] === $user->email);

    Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.kit.com/v4/tags/202/subscribers'
        && $request['email_address'] === $user->email);

});

test('repeated event is idempotent', function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    Mail::fake();

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
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

    $headers = ['Stripe-Signature' => $signature];

    $this->withHeaders($headers)->postJson(route('webhooks.stripe'), $payload)->assertOk();
    $this->withHeaders($headers)->postJson(route('webhooks.stripe'), $payload)->assertOk();

    $this->assertSame(1, StripeEvent::query()->where('stripe_event_id', 'evt_checkout_idempotent')->count());

    $order = Order::query()->firstWhere('stripe_checkout_session_id', 'cs_test_idempotent');

    $this->assertNotNull($order);
    $this->assertSame(1, OrderItem::query()->where('order_id', $order->id)->count());
    $this->assertSame(1, Entitlement::query()->where('order_id', $order->id)->count());
    Mail::assertSent(PurchaseReceiptMail::class, 1);

});

test('async paid event after completed does not send duplicate receipt email', function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    Mail::fake();

    $user = User::factory()->create();
    $course = Course::factory()->published()->create();

    $baseSessionObject = [
        'id' => 'cs_test_async_duplicate',
        'object' => 'checkout.session',
        'currency' => 'usd',
        'amount_subtotal' => 9900,
        'amount_total' => 9900,
        'customer' => 'cus_test_async_duplicate',
        'customer_email' => $user->email,
        'metadata' => [
            'course_id' => (string) $course->id,
            'user_id' => (string) $user->id,
            'customer_email' => $user->email,
        ],
    ];

    $firstPayload = [
        'id' => 'evt_async_duplicate_1',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => ['object' => $baseSessionObject],
    ];

    $secondPayload = [
        'id' => 'evt_async_duplicate_2',
        'object' => 'event',
        'type' => 'checkout.session.async_payment_succeeded',
        'data' => ['object' => $baseSessionObject],
    ];

    $firstSignature = ($this->generateSignatureHeader)(
        json_encode($firstPayload, JSON_THROW_ON_ERROR),
        'whsec_test'
    );

    $secondSignature = ($this->generateSignatureHeader)(
        json_encode($secondPayload, JSON_THROW_ON_ERROR),
        'whsec_test'
    );

    $this->withHeaders(['Stripe-Signature' => $firstSignature])
        ->postJson(route('webhooks.stripe'), $firstPayload)
        ->assertOk();

    $this->withHeaders(['Stripe-Signature' => $secondSignature])
        ->postJson(route('webhooks.stripe'), $secondPayload)
        ->assertOk();

    Mail::assertSent(PurchaseReceiptMail::class, 1);

});

test('invalid signature is rejected', function (): void {
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

});

test('guest checkout creates claim token without entitlement', function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    Mail::fake();

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
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

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

    Mail::assertSent(PurchaseReceiptMail::class, fn (PurchaseReceiptMail $mail): bool => $mail->hasTo('guestbuyer@example.com')
        && is_string($mail->claimUrl)
        && str_contains($mail->claimUrl, '/claim-purchase/'));

});

test('refund webhook revokes entitlements for order', function (): void {
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
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

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

});

test('gift checkout creates gift record and sends gift emails', function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    Mail::fake();
    config()->set('learning.gifts_enabled', true);

    $course = Course::factory()->published()->create();

    Cache::put('gift-checkout:cs_test_gift_1', [
        'recipient_email' => 'recipient@example.com',
        'recipient_name' => 'Recipient Person',
        'gift_message' => 'Enjoy this course!',
    ], now()->addMinutes(30));

    $payload = [
        'id' => 'evt_gift_checkout_1',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_gift_1',
                'object' => 'checkout.session',
                'currency' => 'usd',
                'amount_subtotal' => 9900,
                'amount_total' => 9900,
                'customer_email' => 'buyer@example.com',
                'metadata' => [
                    'course_id' => (string) $course->id,
                    'customer_email' => 'buyer@example.com',
                    'is_gift' => '1',
                    'recipient_email' => 'recipient@example.com',
                    'recipient_name' => 'Recipient Person',
                    'gift_message_present' => '1',
                ],
            ],
        ],
    ];

    $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

    $this->withHeaders([
        'Stripe-Signature' => $signature,
    ])->postJson(route('webhooks.stripe'), $payload)->assertOk();

    $order = Order::query()->firstWhere('stripe_checkout_session_id', 'cs_test_gift_1');
    $this->assertNotNull($order);

    $this->assertDatabaseHas('gift_purchases', [
        'order_id' => $order->id,
        'course_id' => $course->id,
        'buyer_email' => 'buyer@example.com',
        'recipient_email' => 'recipient@example.com',
        'status' => 'delivered',
    ]);

    $this->assertDatabaseHas('purchase_claim_tokens', [
        'order_id' => $order->id,
        'purpose' => 'gift_claim',
    ]);

    $this->assertSame(0, Entitlement::query()->where('order_id', $order->id)->count());

    Mail::assertSent(PurchaseReceiptMail::class, 1);
    Mail::assertSent(GiftDeliveryMail::class, 1);
    Mail::assertSent(GiftPurchaseConfirmationMail::class, 1);

});

test('refund webhook revokes claimed gift', function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');

    $recipient = User::factory()->create();
    $course = Course::factory()->published()->create();

    $order = Order::create([
        'email' => 'buyer@example.com',
        'stripe_checkout_session_id' => 'cs_test_refund_gift',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    $gift = GiftPurchase::create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'buyer_email' => 'buyer@example.com',
        'recipient_email' => $recipient->email,
        'status' => 'claimed',
        'claimed_by_user_id' => $recipient->id,
        'claimed_at' => now(),
    ]);

    PurchaseClaimToken::create([
        'order_id' => $order->id,
        'gift_purchase_id' => $gift->id,
        'purpose' => 'gift_claim',
        'token' => 'gift_refund_token_1',
        'expires_at' => now()->addDay(),
        'consumed_at' => now()->subMinute(),
    ]);

    Entitlement::create([
        'user_id' => $recipient->id,
        'course_id' => $course->id,
        'order_id' => $order->id,
        'status' => 'active',
        'granted_at' => now(),
    ]);

    $payload = [
        'id' => 'evt_refund_gift_1',
        'object' => 'event',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_test_refund_gift',
                'object' => 'charge',
                'metadata' => [
                    'checkout_session_id' => 'cs_test_refund_gift',
                ],
            ],
        ],
    ];

    $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

    $this->withHeaders([
        'Stripe-Signature' => $signature,
    ])->postJson(route('webhooks.stripe'), $payload)->assertOk();

    $this->assertDatabaseHas('gift_purchases', [
        'id' => $gift->id,
        'status' => 'revoked',
    ]);

    $this->assertDatabaseHas('entitlements', [
        'order_id' => $order->id,
        'status' => 'revoked',
    ]);

});

test('subscription webhook creates or updates subscription record', function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');

    $user = User::factory()->create();

    $payload = [
        'id' => 'evt_subscription_created_1',
        'object' => 'event',
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_test_1',
                'object' => 'subscription',
                'customer' => 'cus_sub_test_1',
                'status' => 'active',
                'current_period_start' => now()->subDay()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
                'cancel_at_period_end' => false,
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'customer_email' => $user->email,
                ],
                'items' => [
                    'data' => [[
                        'price' => [
                            'id' => 'price_monthly_1',
                            'recurring' => ['interval' => 'month'],
                        ],
                    ]],
                ],
            ],
        ],
    ];

    $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

    $this->withHeaders([
        'Stripe-Signature' => $signature,
    ])->postJson(route('webhooks.stripe'), $payload)->assertOk();

    $this->assertDatabaseHas('subscriptions', [
        'stripe_subscription_id' => 'sub_test_1',
        'stripe_customer_id' => 'cus_sub_test_1',
        'user_id' => $user->id,
        'status' => 'active',
        'interval' => 'monthly',
    ]);
});

test('invoice paid webhook creates subscription order', function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');

    $user = User::factory()->create();
    $subscription = Subscription::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_customer_id' => 'cus_sub_invoice_1',
        'stripe_subscription_id' => 'sub_invoice_1',
        'stripe_price_id' => 'price_invoice_1',
        'interval' => 'monthly',
        'status' => 'active',
    ]);

    $payload = [
        'id' => 'evt_invoice_paid_1',
        'object' => 'event',
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_test_1',
                'object' => 'invoice',
                'subscription' => $subscription->stripe_subscription_id,
                'currency' => 'usd',
                'subtotal' => 1500,
                'amount_paid' => 1500,
                'total' => 1500,
                'payment_intent' => 'pi_test_invoice_1',
            ],
        ],
    ];

    $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = ($this->generateSignatureHeader)($jsonPayload, 'whsec_test');

    $this->withHeaders([
        'Stripe-Signature' => $signature,
    ])->postJson(route('webhooks.stripe'), $payload)->assertOk();

    $this->assertDatabaseHas('orders', [
        'stripe_checkout_session_id' => 'subinv_in_test_1',
        'order_type' => 'subscription',
        'subscription_id' => $subscription->id,
        'status' => 'paid',
        'total_amount' => 1500,
    ]);
});
