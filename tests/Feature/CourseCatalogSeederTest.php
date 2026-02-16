<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('course catalog seeder creates deterministic catalog data', function (): void {
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

});

test('course lesson factory keeps course and module relationship consistent', function (): void {
    $module = CourseModule::factory()->create();
    $lesson = CourseLesson::factory()->for($module, 'module')->create();

    $this->assertTrue($lesson->module->is($module));
    $this->assertSame($module->course_id, $lesson->course_id);

});
