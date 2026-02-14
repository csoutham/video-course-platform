<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MyCoursesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $courses = $request->user()
            ->entitlements()
            ->active()
            ->with('course')
            ->get()
            ->pluck('course')
            ->filter(fn ($course) => $course && $course->is_published)
            ->sortBy('title')
            ->values();

        return view('learning.my-courses', [
            'courses' => $courses,
        ]);
    }
}
