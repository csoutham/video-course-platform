<?php

use App\Models\Course;
use App\Models\User;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
