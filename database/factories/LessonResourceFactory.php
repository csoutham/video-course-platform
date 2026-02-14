<?php

namespace Database\Factories;

use App\Models\CourseLesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LessonResource>
 */
class LessonResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => CourseLesson::factory(),
            'name' => fake()->words(2, true).'.pdf',
            'storage_key' => 'courses/'.fake()->slug().'/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(4096, 1_200_000),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
