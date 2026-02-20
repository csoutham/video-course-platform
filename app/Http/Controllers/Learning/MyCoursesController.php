<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MyCoursesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $entitlementCourses = $request->user()
            ->entitlements()
            ->active()
            ->with('course')
            ->get()
            ->pluck('course')
            ->filter(fn ($course) => $course && $course->is_published);

        $hasActiveSubscription = (bool) config('learning.subscriptions_enabled')
            && $request->user()
                ->subscriptions()
                ->where(function ($query): void {
                    $query
                        ->whereIn('status', ['active', 'trialing'])
                        ->orWhere(function ($canceled): void {
                            $canceled->where('status', 'canceled')->where('current_period_end', '>', now());
                        });
                })
                ->exists();

        $subscriptionCourses = $hasActiveSubscription
            ? Course::query()
                ->published()
                ->where('is_subscription_excluded', false)
                ->get()
            : collect();

        $courses = $entitlementCourses
            ->concat($subscriptionCourses)
            ->unique('id')
            ->sortBy('title')
            ->values();

        return view('learning.my-courses', [
            'courses' => $courses,
            'hasActiveSubscription' => $hasActiveSubscription,
        ]);
    }
}
