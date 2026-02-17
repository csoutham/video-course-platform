<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonResource;
use App\Models\User;
use App\Services\Learning\CloudflareStreamCatalogService;
use App\Services\Learning\CloudflareStreamMetadataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
    $course = Course::factory()->create([
        'stream_video_filter_term' => 'Monologue Course',
    ]);
    CourseModule::factory()->create(['course_id' => $course->id]);

    $catalog = \Mockery::mock(CloudflareStreamCatalogService::class);
    $catalog->shouldReceive('listVideos')
        ->once()
        ->with(200)
        ->andReturn([
            [
                'uid' => 'uid_abc',
                'name' => 'Monologue Course - Lesson Upload 1',
                'duration_seconds' => 321,
            ],
            [
                'uid' => 'uid_other',
                'name' => 'Different Course - Lesson Upload 2',
                'duration_seconds' => 410,
            ],
        ]);
    $this->app->instance(CloudflareStreamCatalogService::class, $catalog);

    $this->get(route('admin.courses.edit', $course))
        ->assertOk()
        ->assertSeeText('Monologue Course - Lesson Upload 1')
        ->assertSeeText('uid_abc')
        ->assertDontSeeText('Different Course - Lesson Upload 2')
        ->assertSeeText('filtered by course filter');

});

test('admin can update and delete lesson', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $lesson = CourseLesson::factory()->create([
        'slug' => 'intro-lesson',
    ]);
    $targetModule = CourseModule::factory()->create([
        'course_id' => $lesson->course_id,
    ]);

    $this->put(route('admin.lessons.update', $lesson), [
        'title' => 'Updated Lesson',
        'slug' => 'updated-lesson',
        'module_id' => $targetModule->id,
        'summary' => 'Updated summary',
        'sort_order' => 2,
        'duration_seconds' => 180,
        'is_published' => '1',
    ])->assertRedirect(route('admin.courses.edit', $lesson->course_id));

    $this->assertDatabaseHas('course_lessons', [
        'id' => $lesson->id,
        'title' => 'Updated Lesson',
        'slug' => 'updated-lesson',
        'module_id' => $targetModule->id,
        'duration_seconds' => 180,
    ]);

    $this->delete(route('admin.lessons.destroy', $lesson))
        ->assertRedirect(route('admin.courses.edit', $lesson->course_id));

    $this->assertDatabaseMissing('course_lessons', [
        'id' => $lesson->id,
    ]);

});

test('admin cannot move lesson to module in different course', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $lesson = CourseLesson::factory()->create();
    $foreignModule = CourseModule::factory()->create();

    $this->put(route('admin.lessons.update', $lesson), [
        'title' => $lesson->title,
        'slug' => $lesson->slug,
        'module_id' => $foreignModule->id,
        'summary' => $lesson->summary,
        'sort_order' => $lesson->sort_order,
        'duration_seconds' => $lesson->duration_seconds,
        'is_published' => $lesson->is_published ? '1' : '0',
    ])->assertSessionHasErrors('module_id');
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

test('admin can upload and delete course, module, and lesson resources', function (): void {
    Storage::fake('local');
    config()->set('filesystems.course_resources_disk', 'local');

    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create();
    $module = CourseModule::factory()->create(['course_id' => $course->id]);
    $lesson = CourseLesson::factory()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
    ]);

    $this->post(route('admin.resources.course.store', $course), [
        'name' => 'Course Guide.pdf',
        'resource_file' => UploadedFile::fake()->create('course-guide.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('admin.courses.edit', $course));

    $this->post(route('admin.resources.module.store', $module), [
        'name' => 'Module Guide.pdf',
        'resource_file' => UploadedFile::fake()->create('module-guide.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('admin.courses.edit', $course));

    $this->post(route('admin.resources.lesson.store', $lesson), [
        'name' => 'Lesson Guide.pdf',
        'resource_file' => UploadedFile::fake()->create('lesson-guide.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('admin.courses.edit', $course));

    $this->assertDatabaseHas('lesson_resources', [
        'course_id' => $course->id,
        'module_id' => null,
        'lesson_id' => null,
        'name' => 'Course Guide.pdf',
    ]);
    $this->assertDatabaseHas('lesson_resources', [
        'course_id' => $course->id,
        'module_id' => $module->id,
        'lesson_id' => null,
        'name' => 'Module Guide.pdf',
    ]);
    $this->assertDatabaseHas('lesson_resources', [
        'course_id' => $course->id,
        'module_id' => $module->id,
        'lesson_id' => $lesson->id,
        'name' => 'Lesson Guide.pdf',
    ]);

    $resource = LessonResource::query()->firstWhere('name', 'Course Guide.pdf');
    expect($resource)->not->toBeNull();

    $this->delete(route('admin.resources.destroy', $resource))
        ->assertRedirect(route('admin.courses.edit', $course));

    $this->assertDatabaseMissing('lesson_resources', ['id' => $resource->id]);
});
