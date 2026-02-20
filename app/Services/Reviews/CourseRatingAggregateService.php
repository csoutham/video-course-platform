<?php

namespace App\Services\Reviews;

use App\Models\Course;
use App\Models\CourseReview;

class CourseRatingAggregateService
{
    public function refreshForCourse(Course $course): void
    {
        $approved = CourseReview::query()
            ->where('course_id', $course->id)
            ->approved();

        $count = (int) $approved->count();
        $average = $count > 0
            ? round((float) ($approved->avg('rating') ?? 0), 2)
            : null;

        $distributionCounts = CourseReview::query()
            ->where('course_id', $course->id)
            ->approved()
            ->selectRaw('rating, COUNT(*) as aggregate_count')
            ->groupBy('rating')
            ->pluck('aggregate_count', 'rating');

        $distribution = [];
        foreach (range(1, 5) as $rating) {
            $distribution[(string) $rating] = (int) ($distributionCounts[$rating] ?? 0);
        }

        $course->forceFill([
            'reviews_approved_count' => $count,
            'rating_average' => $average,
            'rating_distribution_json' => $count > 0 ? $distribution : null,
        ])->save();
    }
}

