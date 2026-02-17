<?php

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\User;
use App\Mail\GiftDeliveryMail;
use App\Mail\GiftPurchaseConfirmationMail;
use App\Mail\PurchaseReceiptMail;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('guest checkout requires email', function (): void {
    $course = Course::factory()->published()->create([
        'stripe_price_id' => 'price_test_123',
    ]);

    $this->from(route('courses.show', $course->slug))
        ->post(route('checkout.start', $course))
        ->assertRedirect(route('courses.show', $course->slug))
        ->assertSessionHasErrors('email');

});

test('checkout redirects to stripe url for guest purchase', function (): void {
    $course = Course::factory()->published()->create([
        'stripe_price_id' => 'price_test_123',
    ]);

    $mock = \Mockery::mock(StripeCheckoutService::class);
    $mock->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn([
            'url' => 'https://checkout.stripe.test/session/cs_test_123',
            'session_id' => 'cs_test_123',
        ]);

    $this->app->instance(StripeCheckoutService::class, $mock);

    $this->post(route('checkout.start', $course), [
        'email' => 'guest@example.com',
        'promotion_code' => 'promo_123',
    ])->assertRedirect('https://checkout.stripe.test/session/cs_test_123');

    $this->assertDatabaseHas('audit_logs', [
        'event_type' => 'checkout_started',
    ]);

});

test('authenticated user checkout uses account email', function (): void {
    $user = User::factory()->create([
        'email' => 'buyer@example.com',
    ]);

    $course = Course::factory()->published()->create([
        'stripe_price_id' => 'price_test_123',
    ]);

    $mock = \Mockery::mock(StripeCheckoutService::class);
    $mock->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn([
            'url' => 'https://checkout.stripe.test/session/cs_test_456',
            'session_id' => 'cs_test_456',
        ]);

    $this->app->instance(StripeCheckoutService::class, $mock);

    $this->actingAs($user)
        ->post(route('checkout.start', $course))
        ->assertRedirect('https://checkout.stripe.test/session/cs_test_456');

});

test('invalid promotion code returns validation error', function (): void {
    $course = Course::factory()->published()->create([
        'stripe_price_id' => 'price_test_123',
    ]);

    $mock = \Mockery::mock(StripeCheckoutService::class);
    $mock->shouldReceive('createCheckoutSession')
        ->once()
        ->andThrow(new \InvalidArgumentException('Promotion code is invalid or inactive.'));

    $this->app->instance(StripeCheckoutService::class, $mock);

    $this->from(route('courses.show', $course->slug))
        ->post(route('checkout.start', $course), [
            'email' => 'guest@example.com',
            'promotion_code' => 'BADCODE',
        ])
        ->assertRedirect(route('courses.show', $course->slug))
        ->assertSessionHasErrors('promotion_code');

});

test('gift checkout requires recipient email when enabled', function (): void {
    config()->set('learning.gifts_enabled', true);

    $course = Course::factory()->published()->create([
        'stripe_price_id' => 'price_test_123',
    ]);

    $this->from(route('courses.show', $course->slug))
        ->post(route('checkout.start', $course), [
            'email' => 'buyer@example.com',
            'is_gift' => '1',
        ])
        ->assertRedirect(route('courses.show', $course->slug))
        ->assertSessionHasErrors('recipient_email');

});

test('gift checkout is blocked when feature is disabled', function (): void {
    config()->set('learning.gifts_enabled', false);

    $course = Course::factory()->published()->create([
        'stripe_price_id' => 'price_test_123',
    ]);

    $this->from(route('courses.show', $course->slug))
        ->post(route('checkout.start', $course), [
            'email' => 'buyer@example.com',
            'is_gift' => '1',
            'recipient_email' => 'friend@example.com',
        ])
        ->assertRedirect(route('courses.show', $course->slug))
        ->assertSessionHasErrors('recipient_email');

});

test('free guest checkout creates claim flow without stripe', function (): void {
    Mail::fake();

    $course = Course::factory()->published()->create([
        'is_free' => true,
        'free_access_mode' => 'claim_link',
        'price_amount' => 0,
        'stripe_price_id' => null,
    ]);

    $response = $this->post(route('checkout.start', $course), [
        'email' => 'lead@example.com',
    ]);

    $response->assertRedirect();
    expect((string) $response->headers->get('Location'))->toContain('/checkout/success?session_id=free_');

    $order = Order::query()->firstOrFail();

    expect($order->status)->toBe('paid');
    expect($order->total_amount)->toBe(0);
    expect($order->stripe_checkout_session_id)->toStartWith('free_');

    $this->assertDatabaseHas('purchase_claim_tokens', [
        'order_id' => $order->id,
        'purpose' => 'order_claim',
    ]);

    Mail::assertSent(PurchaseReceiptMail::class, fn (PurchaseReceiptMail $mail): bool => $mail->hasTo('lead@example.com'));
});

test('free direct mode grants entitlement for authenticated user', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'member@example.com',
    ]);

    $course = Course::factory()->published()->create([
        'is_free' => true,
        'free_access_mode' => 'direct',
        'price_amount' => 0,
        'stripe_price_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('checkout.start', $course));

    $response->assertRedirect();
    expect((string) $response->headers->get('Location'))->toContain('/checkout/success?session_id=free_');

    $order = Order::query()->firstOrFail();

    $this->assertDatabaseHas('entitlements', [
        'order_id' => $order->id,
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseMissing('purchase_claim_tokens', [
        'order_id' => $order->id,
        'purpose' => 'order_claim',
    ]);

    Mail::assertSent(PurchaseReceiptMail::class, fn (PurchaseReceiptMail $mail): bool => $mail->hasTo('member@example.com'));
});

test('free gift checkout issues gift claim and sends gift emails', function (): void {
    Mail::fake();
    config()->set('learning.gifts_enabled', true);

    $course = Course::factory()->published()->create([
        'is_free' => true,
        'free_access_mode' => 'claim_link',
        'price_amount' => 0,
        'stripe_price_id' => null,
    ]);

    $response = $this->post(route('checkout.start', $course), [
        'email' => 'buyer@example.com',
        'is_gift' => '1',
        'recipient_email' => 'friend@example.com',
        'recipient_name' => 'Friend',
        'gift_message' => 'Enjoy this free training',
    ]);

    $response->assertRedirect();
    expect((string) $response->headers->get('Location'))->toContain('/checkout/success?session_id=free_');

    $gift = GiftPurchase::query()->firstOrFail();

    $this->assertDatabaseHas('gift_purchases', [
        'id' => $gift->id,
        'status' => 'delivered',
        'buyer_email' => 'buyer@example.com',
        'recipient_email' => 'friend@example.com',
    ]);

    $this->assertDatabaseHas('purchase_claim_tokens', [
        'gift_purchase_id' => $gift->id,
        'purpose' => 'gift_claim',
    ]);

    expect(Entitlement::query()->count())->toBe(0);

    Mail::assertSent(GiftDeliveryMail::class, fn (GiftDeliveryMail $mail): bool => $mail->hasTo('friend@example.com'));
    Mail::assertSent(GiftPurchaseConfirmationMail::class, fn (GiftPurchaseConfirmationMail $mail): bool => $mail->hasTo('buyer@example.com'));
});
