<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $mock->shouldReceive('createCheckoutUrl')
            ->once()
            ->andReturn('https://checkout.stripe.test/session/cs_test_123');

        $this->app->instance(StripeCheckoutService::class, $mock);

        $this->post(route('checkout.start', $course), [
            'email' => 'guest@example.com',
            'promotion_code' => 'promo_123',
        ])->assertRedirect('https://checkout.stripe.test/session/cs_test_123');
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
        $mock->shouldReceive('createCheckoutUrl')
            ->once()
            ->andReturn('https://checkout.stripe.test/session/cs_test_456');

        $this->app->instance(StripeCheckoutService::class, $mock);

        $this->actingAs($user)
            ->post(route('checkout.start', $course))
            ->assertRedirect('https://checkout.stripe.test/session/cs_test_456');
    }
}
