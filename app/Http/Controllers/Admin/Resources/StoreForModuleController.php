<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class StoreForModuleController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\ResourcesController::class;
    }

    protected static function targetMethod(): string
    {
        return "storeForModule";
    }
}
