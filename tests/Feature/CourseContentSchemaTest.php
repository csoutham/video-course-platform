<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseContentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_hierarchy_relationships_are_wired_correctly(): void
    {
        $course = Course::create([
            'slug' => 'laravel-foundations',
            'title' => 'Laravel Foundations',
            'description' => 'Core Laravel concepts',
            'price_amount' => 9900,
            'price_currency' => 'usd',
            'is_published' => true,
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Getting Started',
            'sort_order' => 1,
        ]);

        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Install and Setup',
            'slug' => 'install-and-setup',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $resource = LessonResource::create([
            'lesson_id' => $lesson->id,
            'name' => 'Setup Checklist',
            'storage_key' => 'courses/laravel-foundations/setup-checklist.pdf',
            'sort_order' => 1,
        ]);

        $this->assertTrue($module->course->is($course));
        $this->assertTrue($lesson->course->is($course));
        $this->assertTrue($lesson->module->is($module));
        $this->assertTrue($resource->lesson->is($lesson));
    }

    public function test_ordered_relationships_sort_by_sort_order(): void
    {
        $course = Course::create([
            'slug' => 'advanced-laravel',
            'title' => 'Advanced Laravel',
            'description' => 'Deep dive',
            'price_amount' => 12900,
            'price_currency' => 'usd',
            'is_published' => true,
        ]);

        $moduleB = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'B Module',
            'sort_order' => 20,
        ]);

        $moduleA = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'A Module',
            'sort_order' => 10,
        ]);

        $lesson2 = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $moduleA->id,
            'title' => 'Second Lesson',
            'slug' => 'second-lesson',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $lesson1 = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $moduleA->id,
            'title' => 'First Lesson',
            'slug' => 'first-lesson',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        LessonResource::create([
            'lesson_id' => $lesson1->id,
            'name' => 'Second Resource',
            'storage_key' => 'courses/advanced/second.pdf',
            'sort_order' => 2,
        ]);

        LessonResource::create([
            'lesson_id' => $lesson1->id,
            'name' => 'First Resource',
            'storage_key' => 'courses/advanced/first.pdf',
            'sort_order' => 1,
        ]);

        $this->assertSame([$moduleA->id, $moduleB->id], $course->modules()->pluck('id')->all());
        $this->assertSame([$lesson1->id, $lesson2->id], $moduleA->lessons()->pluck('id')->all());
        $this->assertSame([1, 2], $lesson1->resources()->pluck('sort_order')->all());
    }

    public function test_published_scope_excludes_unpublished_courses_and_lessons(): void
    {
        Course::create([
            'slug' => 'published-course',
            'title' => 'Published',
            'description' => null,
            'price_amount' => 1000,
            'price_currency' => 'usd',
            'is_published' => true,
        ]);

        Course::create([
            'slug' => 'draft-course',
            'title' => 'Draft',
            'description' => null,
            'price_amount' => 1000,
            'price_currency' => 'usd',
            'is_published' => false,
        ]);

        $course = Course::firstWhere('slug', 'published-course');
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module',
            'sort_order' => 1,
        ]);

        CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Published Lesson',
            'slug' => 'published-lesson',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Draft Lesson',
            'slug' => 'draft-lesson',
            'sort_order' => 2,
            'is_published' => false,
        ]);

        $this->assertSame(['published-course'], Course::published()->pluck('slug')->all());
        $this->assertSame(['published-lesson'], CourseLesson::published()->pluck('slug')->all());
    }
}
