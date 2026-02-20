<?php

namespace App\Http\Controllers\Admin\CourseModules;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class StoreController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\CourseModulesController::class;
    }

    protected static function targetMethod(): string
    {
        return "store";
    }
}
