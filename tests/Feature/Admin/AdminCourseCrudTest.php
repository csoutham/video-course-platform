<?php

use App\Models\Course;
use App\Models\User;
use App\Services\Learning\CloudflareStreamMetadataService;
use App\Services\Payments\StripeCoursePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can view create course screen', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.courses.create'))
        ->assertOk()
        ->assertSeeText('Create Course');

});

test('admin can create course and auto assign stripe price', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $mock = \Mockery::mock(StripeCoursePricingService::class);
    $mock->shouldReceive('createPriceForCourse')
        ->once()
        ->andReturn('price_auto_123');
    $this->app->instance(StripeCoursePricingService::class, $mock);

    $response = $this->post(route('admin.courses.store'), [
        'title' => 'Laravel for Teams',
        'description' => 'Team-focused Laravel training.',
        'long_description' => "## Long description\n\nBuilt for team learning.",
        'requirements' => "- PHP 8.4\n- Composer",
        'price_amount' => 12900,
        'price_currency' => 'usd',
        'is_published' => '1',
        'auto_create_stripe_price' => '1',
    ]);

    $course = Course::query()->firstOrFail();

    $response->assertRedirect(route('admin.courses.edit', $course));

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'title' => 'Laravel for Teams',
        'long_description' => "## Long description\n\nBuilt for team learning.",
        'requirements' => "- PHP 8.4\n- Composer",
        'stripe_price_id' => 'price_auto_123',
        'is_published' => true,
    ]);

});

test('admin can update course and refresh stripe price', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create([
        'stripe_price_id' => 'price_old_1',
        'price_amount' => 9900,
    ]);

    $mock = \Mockery::mock(StripeCoursePricingService::class);
    $mock->shouldReceive('createPriceForCourse')
        ->once()
        ->andReturn('price_new_2');
    $this->app->instance(StripeCoursePricingService::class, $mock);

    $metadataMock = \Mockery::mock(CloudflareStreamMetadataService::class);
    $metadataMock->shouldReceive('requireSignedUrls')
        ->once()
        ->with('stream_intro_001');
    $this->app->instance(CloudflareStreamMetadataService::class, $metadataMock);

    $response = $this->put(route('admin.courses.update', $course), [
        'title' => 'Updated Course Title',
        'slug' => $course->slug,
        'description' => 'Updated description',
        'long_description' => "### Updated long description\n\nMore depth.",
        'requirements' => "- Git\n- Basic Laravel",
        'thumbnail_url' => 'https://example.com/new.jpg',
        'intro_video_id' => 'stream_intro_001',
        'price_amount' => 14900,
        'price_currency' => 'usd',
        'stripe_price_id' => $course->stripe_price_id,
        'refresh_stripe_price' => '1',
        'is_published' => '1',
    ]);

    $response->assertRedirect(route('admin.courses.edit', $course));

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'title' => 'Updated Course Title',
        'price_amount' => 14900,
        'long_description' => "### Updated long description\n\nMore depth.",
        'requirements' => "- Git\n- Basic Laravel",
        'intro_video_id' => 'stream_intro_001',
        'stripe_price_id' => 'price_new_2',
        'is_published' => true,
    ]);

});

test('admin can create course with intro video id', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $pricingMock = \Mockery::mock(StripeCoursePricingService::class);
    $pricingMock->shouldReceive('createPriceForCourse')
        ->once()
        ->andReturn('price_auto_intro_123');
    $this->app->instance(StripeCoursePricingService::class, $pricingMock);

    $metadataMock = \Mockery::mock(CloudflareStreamMetadataService::class);
    $metadataMock->shouldReceive('requireSignedUrls')
        ->once()
        ->with('stream_intro_create_001');
    $this->app->instance(CloudflareStreamMetadataService::class, $metadataMock);

    $response = $this->post(route('admin.courses.store'), [
        'title' => 'Course With Intro Video',
        'description' => 'Has intro clip',
        'intro_video_id' => 'stream_intro_create_001',
        'price_amount' => 12900,
        'price_currency' => 'usd',
        'is_published' => '1',
        'auto_create_stripe_price' => '1',
    ]);

    $course = Course::query()->firstOrFail();
    $response->assertRedirect(route('admin.courses.edit', $course));

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'intro_video_id' => 'stream_intro_create_001',
    ]);
});
