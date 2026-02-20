<?php

use App\Models\Course;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingPortalService;
use App\Services\Billing\SubscriptionCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot start subscription checkout', function (): void {
    config()->set('learning.subscriptions_enabled', true);

    $this->post(route('checkout.subscription.start'), [
        'interval' => 'monthly',
    ])->assertRedirect(route('login'));
});

test('subscription checkout route is disabled when feature flag is off', function (): void {
    config()->set('learning.subscriptions_enabled', false);

    $this->actingAs(User::factory()->create())
        ->post(route('checkout.subscription.start'), [
            'interval' => 'monthly',
        ])->assertNotFound();
});

test('authenticated user can start subscription checkout', function (): void {
    config()->set('learning.subscriptions_enabled', true);

    $user = User::factory()->create();

    $mock = \Mockery::mock(SubscriptionCheckoutService::class);
    $mock->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn([
            'url' => 'https://checkout.stripe.test/session/cs_sub_123',
            'session_id' => 'cs_sub_123',
        ]);

    $this->app->instance(SubscriptionCheckoutService::class, $mock);

    $this->actingAs($user)
        ->post(route('checkout.subscription.start'), [
            'interval' => 'monthly',
        ])->assertRedirect('https://checkout.stripe.test/session/cs_sub_123');
});

test('billing page renders active subscription', function (): void {
    config()->set('learning.subscriptions_enabled', true);

    $user = User::factory()->create();

    Subscription::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_customer_id' => 'cus_sub_1',
        'stripe_subscription_id' => 'sub_1',
        'stripe_price_id' => 'price_1',
        'interval' => 'monthly',
        'status' => 'active',
        'current_period_start' => now()->subDay(),
        'current_period_end' => now()->addMonth(),
    ]);

    $this->actingAs($user)
        ->get(route('billing.show'))
        ->assertOk()
        ->assertSeeText('Subscription')
        ->assertSeeText('ACTIVE');
});

test('billing portal redirects through stripe portal service', function (): void {
    config()->set('learning.subscriptions_enabled', true);

    $user = User::factory()->create();

    $mock = \Mockery::mock(BillingPortalService::class);
    $mock->shouldReceive('createPortalUrl')
        ->once()
        ->andReturn('https://billing.stripe.test/portal/session_1');

    $this->app->instance(BillingPortalService::class, $mock);

    $this->actingAs($user)
        ->post(route('billing.portal'))
        ->assertRedirect('https://billing.stripe.test/portal/session_1');
});

test('active subscription grants course access when course is not excluded', function (): void {
    config()->set('learning.subscriptions_enabled', true);

    $user = User::factory()->create();
    $course = Course::factory()->published()->create([
        'is_subscription_excluded' => false,
    ]);

    Subscription::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_customer_id' => 'cus_sub_2',
        'stripe_subscription_id' => 'sub_2',
        'stripe_price_id' => 'price_2',
        'interval' => 'monthly',
        'status' => 'active',
        'current_period_start' => now()->subDay(),
        'current_period_end' => now()->addMonth(),
    ]);

    $this->actingAs($user)
        ->get(route('my-courses.index'))
        ->assertOk()
        ->assertSeeText($course->title);
});

test('excluded courses are not granted by subscription access', function (): void {
    config()->set('learning.subscriptions_enabled', true);

    $user = User::factory()->create();
    $course = Course::factory()->published()->create([
        'is_subscription_excluded' => true,
    ]);

    $module = $course->modules()->create([
        'title' => 'Module 1',
        'sort_order' => 1,
    ]);

    $lesson = $module->lessons()->create([
        'course_id' => $course->id,
        'title' => 'Lesson 1',
        'slug' => 'lesson-1',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    Subscription::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_customer_id' => 'cus_sub_3',
        'stripe_subscription_id' => 'sub_3',
        'stripe_price_id' => 'price_3',
        'interval' => 'monthly',
        'status' => 'active',
        'current_period_start' => now()->subDay(),
        'current_period_end' => now()->addMonth(),
    ]);

    $this->actingAs($user)
        ->get(route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]))
        ->assertForbidden();
});
