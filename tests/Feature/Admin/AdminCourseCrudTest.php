<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use App\Services\Payments\StripeCoursePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminCourseCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_create_course_screen(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.courses.create'))
            ->assertOk()
            ->assertSeeText('Create Course');
    }

    public function test_admin_can_create_course_and_auto_assign_stripe_price(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $mock = Mockery::mock(StripeCoursePricingService::class);
        $mock->shouldReceive('createPriceForCourse')
            ->once()
            ->andReturn('price_auto_123');
        $this->app->instance(StripeCoursePricingService::class, $mock);

        $response = $this->post(route('admin.courses.store'), [
            'title' => 'Laravel for Teams',
            'description' => 'Team-focused Laravel training.',
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
            'stripe_price_id' => 'price_auto_123',
            'is_published' => true,
        ]);
    }

    public function test_admin_can_update_course_and_refresh_stripe_price(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $course = Course::factory()->create([
            'stripe_price_id' => 'price_old_1',
            'price_amount' => 9900,
        ]);

        $mock = Mockery::mock(StripeCoursePricingService::class);
        $mock->shouldReceive('createPriceForCourse')
            ->once()
            ->andReturn('price_new_2');
        $this->app->instance(StripeCoursePricingService::class, $mock);

        $response = $this->put(route('admin.courses.update', $course), [
            'title' => 'Updated Course Title',
            'slug' => $course->slug,
            'description' => 'Updated description',
            'thumbnail_url' => 'https://example.com/new.jpg',
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
            'stripe_price_id' => 'price_new_2',
            'is_published' => true,
        ]);
    }
}
