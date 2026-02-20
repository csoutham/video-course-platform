<?php

namespace App\Http\Controllers\Admin\CourseLessons;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class DestroyController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\CourseLessonsController::class;
    }

    protected static function targetMethod(): string
    {
        return "destroy";
    }
}
