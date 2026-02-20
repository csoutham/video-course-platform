<?php

namespace App\Http\Controllers\Admin\Courses;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class UpdateController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\CoursesController::class;
    }

    protected static function targetMethod(): string
    {
        return "update";
    }
}
