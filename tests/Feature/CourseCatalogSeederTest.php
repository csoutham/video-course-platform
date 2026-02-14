<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_catalog_seeder_creates_deterministic_catalog_data(): void
    {
        $this->seed();

        $this->assertDatabaseHas('courses', [
            'slug' => 'laravel-foundations',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('courses', [
            'slug' => 'internal-release-draft',
            'is_published' => false,
        ]);

        $publishedCourse = Course::query()->firstWhere('slug', 'laravel-foundations');

        $this->assertNotNull($publishedCourse);
        $this->assertSame(2, $publishedCourse->modules()->count());
        $this->assertSame(3, $publishedCourse->lessons()->count());
    }

    public function test_course_lesson_factory_keeps_course_and_module_relationship_consistent(): void
    {
        $module = CourseModule::factory()->create();
        $lesson = CourseLesson::factory()->for($module, 'module')->create();

        $this->assertTrue($lesson->module->is($module));
        $this->assertSame($module->course_id, $lesson->course_id);
    }
}
