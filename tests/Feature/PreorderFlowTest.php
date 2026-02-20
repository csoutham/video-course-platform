<?php

use App\Models\Course;
use App\Services\Preorders\PreorderCheckoutService;
use App\Services\Preorders\PreorderReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('guest preorder requires email', function (): void {
    config()->set('learning.preorders_enabled', true);

    $course = Course::factory()->published()->create([
        'is_preorder_enabled' => true,
        'release_at' => now()->addWeek(),
        'preorder_starts_at' => now()->subDay(),
        'preorder_ends_at' => now()->addDays(5),
        'preorder_price_amount' => 7900,
    ]);

    $this->from(route('courses.show', $course->slug))
        ->post(route('preorder.start', $course))
        ->assertRedirect(route('courses.show', $course->slug))
        ->assertSessionHasErrors('email');
});

test('preorder checkout redirects to stripe for valid request', function (): void {
    config()->set('learning.preorders_enabled', true);

    $course = Course::factory()->published()->create([
        'is_preorder_enabled' => true,
        'release_at' => now()->addWeek(),
        'preorder_starts_at' => now()->subDay(),
        'preorder_ends_at' => now()->addDays(5),
        'preorder_price_amount' => 7900,
    ]);

    $mock = \Mockery::mock(PreorderCheckoutService::class);
    $mock->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn([
            'url' => 'https://checkout.stripe.test/session/cs_preorder_1',
            'session_id' => 'cs_preorder_1',
        ]);

    $this->app->instance(PreorderCheckoutService::class, $mock);

    $this->post(route('preorder.start', $course), [
        'email' => 'guest@example.com',
    ])->assertRedirect('https://checkout.stripe.test/session/cs_preorder_1');
});

test('preorder release command executes preorder release service', function (): void {
    config()->set('learning.preorders_enabled', true);

    $mock = \Mockery::mock(PreorderReleaseService::class);
    $mock->shouldReceive('releaseDueReservations')->once()->andReturn([
        'processed' => 2,
        'charged' => 1,
        'failed' => 1,
    ]);

    $this->app->instance(PreorderReleaseService::class, $mock);

    Artisan::call('videocourses:preorders-release');

    expect(Artisan::output())->toContain('Processed 2 reservation(s): 1 charged, 1 failed.');
});
