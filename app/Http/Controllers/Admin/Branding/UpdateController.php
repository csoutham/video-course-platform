<?php

namespace App\Http\Controllers\Admin\Branding;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class UpdateController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\BrandingController::class;
    }

    protected static function targetMethod(): string
    {
        return "update";
    }
}
