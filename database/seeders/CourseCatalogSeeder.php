<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonResource;
use Illuminate\Database\Seeder;

class CourseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $publishedCourse = Course::query()->updateOrCreate(
            ['slug' => 'laravel-foundations'],
            [
                'title' => 'Laravel Foundations',
                'description' => 'Learn core Laravel patterns from routing to Eloquent basics.',
                'thumbnail_url' => null,
                'price_amount' => 9900,
                'price_currency' => 'usd',
                'stripe_price_id' => null,
                'is_published' => true,
            ]
        );

        $draftCourse = Course::query()->updateOrCreate(
            ['slug' => 'internal-release-draft'],
            [
                'title' => 'Internal Release Draft',
                'description' => 'Draft course used for internal staging checks.',
                'thumbnail_url' => null,
                'price_amount' => 4900,
                'price_currency' => 'usd',
                'stripe_price_id' => null,
                'is_published' => false,
            ]
        );

        $moduleA = CourseModule::query()->updateOrCreate(
            ['course_id' => $publishedCourse->id, 'title' => 'Module 1: Getting Started'],
            ['sort_order' => 1]
        );

        $moduleB = CourseModule::query()->updateOrCreate(
            ['course_id' => $publishedCourse->id, 'title' => 'Module 2: Core Patterns'],
            ['sort_order' => 2]
        );

        $lessonA1 = CourseLesson::query()->updateOrCreate(
            ['course_id' => $publishedCourse->id, 'slug' => 'install-and-setup'],
            [
                'module_id' => $moduleA->id,
                'title' => 'Install and Setup',
                'summary' => 'Environment setup and first request lifecycle.',
                'stream_video_id' => null,
                'sort_order' => 1,
                'is_published' => true,
            ]
        );

        CourseLesson::query()->updateOrCreate(
            ['course_id' => $publishedCourse->id, 'slug' => 'routing-basics'],
            [
                'module_id' => $moduleA->id,
                'title' => 'Routing Basics',
                'summary' => 'HTTP routes, controllers, and named routes.',
                'stream_video_id' => null,
                'sort_order' => 2,
                'is_published' => true,
            ]
        );

        CourseLesson::query()->updateOrCreate(
            ['course_id' => $publishedCourse->id, 'slug' => 'eloquent-intro'],
            [
                'module_id' => $moduleB->id,
                'title' => 'Eloquent Intro',
                'summary' => 'Models, queries, and basic relationships.',
                'stream_video_id' => null,
                'sort_order' => 1,
                'is_published' => true,
            ]
        );

        CourseLesson::query()->updateOrCreate(
            ['course_id' => $draftCourse->id, 'slug' => 'draft-lesson'],
            [
                'module_id' => CourseModule::query()->updateOrCreate(
                    ['course_id' => $draftCourse->id, 'title' => 'Draft Module'],
                    ['sort_order' => 1]
                )->id,
                'title' => 'Draft Lesson',
                'summary' => 'Unpublished lesson used for visibility checks.',
                'stream_video_id' => null,
                'sort_order' => 1,
                'is_published' => false,
            ]
        );

        LessonResource::query()->updateOrCreate(
            ['lesson_id' => $lessonA1->id, 'storage_key' => 'courses/laravel-foundations/install-checklist.pdf'],
            [
                'name' => 'Install Checklist',
                'mime_type' => 'application/pdf',
                'size_bytes' => 156000,
                'sort_order' => 1,
            ]
        );

        LessonResource::query()->updateOrCreate(
            ['lesson_id' => $lessonA1->id, 'storage_key' => 'courses/laravel-foundations/env-template.zip'],
            [
                'name' => 'Environment Template',
                'mime_type' => 'application/zip',
                'size_bytes' => 48200,
                'sort_order' => 2,
            ]
        );
    }
}
