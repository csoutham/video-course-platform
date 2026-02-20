<?php

namespace App\Http\Controllers\Admin\CourseResources;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class StoreForModuleController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\CourseResourcesController::class;
    }

    protected static function targetMethod(): string
    {
        return "storeForModule";
    }
}
