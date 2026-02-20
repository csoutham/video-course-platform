<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('learning.reviews_enabled', true);
});

test('non admin users cannot access admin reviews', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.reviews.index'))
        ->assertForbidden();
});

test('admin reviews queue remains available even when public reviews are disabled', function (): void {
    config()->set('learning.reviews_enabled', false);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.reviews.index'))
        ->assertOk();
});

test('admin can view reviews queue and moderate pending review', function (): void {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->published()->create();
    $reviewer = User::factory()->create();

    $review = CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $reviewer->id,
        'source' => CourseReview::SOURCE_NATIVE,
        'rating' => 4,
        'title' => 'Pending item',
        'status' => CourseReview::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reviews.index'))
        ->assertOk()
        ->assertSeeText('Ratings and Reviews')
        ->assertSeeText('Pending item');

    $this->actingAs($admin)
        ->post(route('admin.reviews.approve', $review))
        ->assertRedirect();

    $review->refresh();
    expect($review->status)->toBe(CourseReview::STATUS_APPROVED);

    $course->refresh();
    expect($course->reviews_approved_count)->toBe(1);
    expect((float) $course->rating_average)->toBe(4.0);
});

test('admin can preview and commit manual imported reviews for a course', function (): void {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->published()->create();

    $sourceText = "5 | Jane Doe | Great course | Loved this training | 2025-12-01\n".
        "4 | Mark Bell | Helpful | Nice examples | 2025-12-03";

    $previewResponse = $this->actingAs($admin)->post(route('admin.courses.reviews.import.preview', $course), [
        'source_text' => $sourceText,
    ]);

    $previewResponse->assertRedirect(route('admin.courses.edit', $course).'#reviews');

    $rows = session()->get('course_review_import_preview:'.$course->id);
    expect($rows)->toBeArray()->and(count($rows))->toBe(2);

    $this->actingAs($admin)->post(route('admin.courses.reviews.import.commit', $course), [
        'rows' => $rows,
    ])->assertRedirect(route('admin.courses.edit', $course).'#reviews');

    $this->assertDatabaseHas('course_reviews', [
        'course_id' => $course->id,
        'source' => CourseReview::SOURCE_UDEMY_MANUAL,
        'reviewer_name' => 'Jane Doe',
        'status' => CourseReview::STATUS_APPROVED,
        'rating' => 5,
    ]);

    $this->assertDatabaseHas('course_reviews', [
        'course_id' => $course->id,
        'source' => CourseReview::SOURCE_UDEMY_MANUAL,
        'reviewer_name' => 'Mark Bell',
        'status' => CourseReview::STATUS_APPROVED,
        'rating' => 4,
    ]);

    $course->refresh();
    expect($course->reviews_approved_count)->toBe(2);
    expect((float) $course->rating_average)->toBe(4.5);
});
