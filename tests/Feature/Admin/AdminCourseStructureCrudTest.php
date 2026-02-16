<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\User;
use App\Services\Learning\CloudflareStreamCatalogService;
use App\Services\Learning\CloudflareStreamMetadataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create module', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create();

    $this->post(route('admin.modules.store', $course), [
        'title' => 'Getting Started',
        'sort_order' => 1,
    ])->assertRedirect(route('admin.courses.edit', $course));

    $this->assertDatabaseHas('course_modules', [
        'course_id' => $course->id,
        'title' => 'Getting Started',
        'sort_order' => 1,
    ]);

});

test('admin can create lesson and sync duration from stream', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $module = CourseModule::factory()->create();

    $metadata = \Mockery::mock(CloudflareStreamMetadataService::class);
    $metadata->shouldReceive('requireSignedUrls')
        ->once()
        ->with('stream_uid_123');
    $metadata->shouldReceive('durationSeconds')
        ->once()
        ->with('stream_uid_123')
        ->andReturn(412);
    $this->app->instance(CloudflareStreamMetadataService::class, $metadata);

    $this->post(route('admin.lessons.store', $module), [
        'title' => 'Lesson One',
        'stream_video_id' => 'stream_uid_123',
        'is_published' => '1',
    ])->assertRedirect(route('admin.courses.edit', $module->course_id));

    $this->assertDatabaseHas('course_lessons', [
        'course_id' => $module->course_id,
        'module_id' => $module->id,
        'title' => 'Lesson One',
        'stream_video_id' => 'stream_uid_123',
        'duration_seconds' => 412,
        'is_published' => true,
    ]);

});

test('admin can view stream options on course edit page', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create();
    CourseModule::factory()->create(['course_id' => $course->id]);

    $catalog = \Mockery::mock(CloudflareStreamCatalogService::class);
    $catalog->shouldReceive('listVideos')
        ->once()
        ->with(200)
        ->andReturn([
            [
                'uid' => 'uid_abc',
                'name' => 'Lesson Upload 1',
                'duration_seconds' => 321,
            ],
        ]);
    $this->app->instance(CloudflareStreamCatalogService::class, $catalog);

    $this->get(route('admin.courses.edit', $course))
        ->assertOk()
        ->assertSeeText('Lesson Upload 1')
        ->assertSeeText('uid_abc');

});

test('admin can update and delete lesson', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $lesson = CourseLesson::factory()->create([
        'slug' => 'intro-lesson',
    ]);

    $this->put(route('admin.lessons.update', $lesson), [
        'title' => 'Updated Lesson',
        'slug' => 'updated-lesson',
        'summary' => 'Updated summary',
        'sort_order' => 2,
        'duration_seconds' => 180,
        'is_published' => '1',
    ])->assertRedirect(route('admin.courses.edit', $lesson->course_id));

    $this->assertDatabaseHas('course_lessons', [
        'id' => $lesson->id,
        'title' => 'Updated Lesson',
        'slug' => 'updated-lesson',
        'duration_seconds' => 180,
    ]);

    $this->delete(route('admin.lessons.destroy', $lesson))
        ->assertRedirect(route('admin.courses.edit', $lesson->course_id));

    $this->assertDatabaseMissing('course_lessons', [
        'id' => $lesson->id,
    ]);

});

test('lesson update with stream video enforces signed urls and syncs duration', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $lesson = CourseLesson::factory()->create([
        'stream_video_id' => 'stream_old',
        'duration_seconds' => 90,
    ]);

    $metadata = \Mockery::mock(CloudflareStreamMetadataService::class);
    $metadata->shouldReceive('requireSignedUrls')
        ->once()
        ->with('stream_uid_456');
    $metadata->shouldReceive('durationSeconds')
        ->once()
        ->with('stream_uid_456')
        ->andReturn(640);
    $this->app->instance(CloudflareStreamMetadataService::class, $metadata);

    $this->put(route('admin.lessons.update', $lesson), [
        'title' => $lesson->title,
        'slug' => $lesson->slug,
        'summary' => $lesson->summary,
        'stream_video_id' => 'stream_uid_456',
        'sort_order' => $lesson->sort_order,
        'is_published' => $lesson->is_published ? '1' : '0',
    ])->assertRedirect(route('admin.courses.edit', $lesson->course_id));

    $this->assertDatabaseHas('course_lessons', [
        'id' => $lesson->id,
        'stream_video_id' => 'stream_uid_456',
        'duration_seconds' => 640,
    ]);

});
