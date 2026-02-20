<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'title' => $title,
            'description' => fake()->paragraph(),
            'long_description' => null,
            'requirements' => null,
            'thumbnail_url' => fake()->imageUrl(1280, 720),
            'intro_video_id' => null,
            'stream_video_filter_term' => null,
            'price_amount' => fake()->numberBetween(4900, 19900),
            'price_currency' => 'usd',
            'stripe_price_id' => null,
            'is_free' => false,
            'free_access_mode' => 'claim_link',
            'is_published' => true,
            'is_subscription_excluded' => false,
            'is_preorder_enabled' => false,
            'preorder_starts_at' => null,
            'preorder_ends_at' => null,
            'release_at' => null,
            'preorder_price_amount' => null,
            'stripe_preorder_price_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
