<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class CheckoutStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_checkout_requires_email(): void
    {
        $course = Course::factory()->published()->create([
            'stripe_price_id' => 'price_test_123',
        ]);

        $this->from(route('courses.show', $course->slug))
            ->post(route('checkout.start', $course))
            ->assertRedirect(route('courses.show', $course->slug))
            ->assertSessionHasErrors('email');
    }

    public function test_checkout_redirects_to_stripe_url_for_guest_purchase(): void
    {
        $course = Course::factory()->published()->create([
            'stripe_price_id' => 'price_test_123',
        ]);

        $mock = Mockery::mock(StripeCheckoutService::class);
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
    }

    public function test_authenticated_user_checkout_uses_account_email(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
        ]);

        $course = Course::factory()->published()->create([
            'stripe_price_id' => 'price_test_123',
        ]);

        $mock = Mockery::mock(StripeCheckoutService::class);
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
    }

    public function test_invalid_promotion_code_returns_validation_error(): void
    {
        $course = Course::factory()->published()->create([
            'stripe_price_id' => 'price_test_123',
        ]);

        $mock = Mockery::mock(StripeCheckoutService::class);
        $mock->shouldReceive('createCheckoutSession')
            ->once()
            ->andThrow(new InvalidArgumentException('Promotion code is invalid or inactive.'));

        $this->app->instance(StripeCheckoutService::class, $mock);

        $this->from(route('courses.show', $course->slug))
            ->post(route('checkout.start', $course), [
                'email' => 'guest@example.com',
                'promotion_code' => 'BADCODE',
            ])
            ->assertRedirect(route('courses.show', $course->slug))
            ->assertSessionHasErrors('promotion_code');
    }

    public function test_gift_checkout_requires_recipient_email_when_enabled(): void
    {
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
    }

    public function test_gift_checkout_is_blocked_when_feature_is_disabled(): void
    {
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
    }
}
