<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\CourseReview;
use App\Models\Entitlement;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('learning.reviews_enabled', true);
});

test('eligible learner can submit a rating and review which is pending moderation', function (): void {
    [$user, $course] = createReviewEligibleUserAndCourse(40);

    $response = $this->actingAs($user)->post(route('courses.reviews.store', $course), [
        'rating' => 5,
        'title' => 'Great pacing',
        'body' => 'Clear lessons and practical steps.',
    ]);

    $response
        ->assertRedirect(route('courses.show', $course->slug))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('course_reviews', [
        'course_id' => $course->id,
        'user_id' => $user->id,
        'source' => CourseReview::SOURCE_NATIVE,
        'rating' => 5,
        'status' => CourseReview::STATUS_PENDING,
    ]);

    $course->refresh();
    expect($course->reviews_approved_count)->toBe(0);
    expect($course->rating_average)->toBeNull();
});

test('learner cannot submit review before required progress threshold', function (): void {
    [$user, $course] = createReviewEligibleUserAndCourse(10);

    $this->actingAs($user)->post(route('courses.reviews.store', $course), [
        'rating' => 4,
        'title' => 'Too early',
    ])->assertRedirect()->assertSessionHasErrors('review');

    $this->assertDatabaseCount('course_reviews', 0);
});

test('editing an approved learner review re-enters moderation and updates aggregates', function (): void {
    [$user, $course] = createReviewEligibleUserAndCourse(60);

    $review = CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
        'source' => CourseReview::SOURCE_NATIVE,
        'rating' => 5,
        'title' => 'Approved',
        'status' => CourseReview::STATUS_APPROVED,
        'approved_at' => now()->subDay(),
    ]);

    $course->forceFill([
        'reviews_approved_count' => 1,
        'rating_average' => 5.0,
        'rating_distribution_json' => ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 1],
    ])->save();

    $this->actingAs($user)->post(route('courses.reviews.store', $course), [
        'rating' => 2,
        'title' => 'Updated',
        'body' => 'Needs improvement.',
    ])->assertRedirect(route('courses.show', $course->slug));

    $review->refresh();
    expect($review->rating)->toBe(2);
    expect($review->status)->toBe(CourseReview::STATUS_PENDING);

    $course->refresh();
    expect($course->reviews_approved_count)->toBe(0);
    expect($course->rating_average)->toBeNull();
});

test('approved reviews render on detail page and pending reviews stay hidden', function (): void {
    $course = Course::factory()->published()->create([
        'title' => 'Review Surface Course',
        'slug' => 'review-surface-course',
    ]);

    $module = CourseModule::factory()->create(['course_id' => $course->id]);
    CourseLesson::factory()->published()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
    ]);

    $approvedUser = User::factory()->create(['name' => 'Alice Example']);
    CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => $approvedUser->id,
        'source' => CourseReview::SOURCE_NATIVE,
        'rating' => 5,
        'title' => 'Visible Review',
        'body' => 'Appears on page.',
        'status' => CourseReview::STATUS_APPROVED,
        'approved_at' => now(),
    ]);

    CourseReview::query()->create([
        'course_id' => $course->id,
        'user_id' => User::factory()->create()->id,
        'source' => CourseReview::SOURCE_NATIVE,
        'rating' => 1,
        'title' => 'Hidden Pending',
        'body' => 'Should not render.',
        'status' => CourseReview::STATUS_PENDING,
    ]);

    $course->forceFill([
        'reviews_approved_count' => 1,
        'rating_average' => 5.0,
        'rating_distribution_json' => ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 1],
    ])->save();

    $this->get(route('courses.show', $course->slug))
        ->assertOk()
        ->assertSee('Ratings and Reviews')
        ->assertSee('Visible Review')
        ->assertDontSee('Hidden Pending');
});

/**
 * @return array{0:User,1:Course}
 */
function createReviewEligibleUserAndCourse(int $progressPercent): array
{
    $user = User::factory()->create();
    $course = Course::factory()->published()->create();
    $module = CourseModule::factory()->create(['course_id' => $course->id]);
    $lesson = CourseLesson::factory()->published()->create([
        'course_id' => $course->id,
        'module_id' => $module->id,
    ]);

    $order = Order::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_test_reviews_'.$course->id,
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    Entitlement::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'order_id' => $order->id,
        'status' => 'active',
        'granted_at' => now(),
    ]);

    LessonProgress::query()->create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => $progressPercent >= 100 ? 'completed' : 'in_progress',
        'percent_complete' => $progressPercent,
        'playback_position_seconds' => 30,
        'video_duration_seconds' => 100,
        'started_at' => now()->subMinutes(20),
        'last_viewed_at' => now()->subMinutes(1),
    ]);

    return [$user, $course];
}

