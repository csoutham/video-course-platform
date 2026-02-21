<?php

namespace App\Http\Controllers\Admin\Courses\Certificate;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;

class EditController extends Controller
{
    public function __invoke(Course $course): View
    {
        return view('admin.courses.certificate', [
            'course' => $course,
        ]);
    }
}
