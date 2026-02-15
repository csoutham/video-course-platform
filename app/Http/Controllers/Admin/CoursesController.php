<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;

class CoursesController extends Controller
{
    public function index(): View
    {
        $courses = Course::query()
            ->withCount(['modules', 'lessons'])
            ->orderBy('title')
            ->paginate(20);

        return view('admin.courses.index', [
            'courses' => $courses,
        ]);
    }
}
