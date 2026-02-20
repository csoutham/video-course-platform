<?php

namespace App\Http\Controllers\Api\V1\Mobile\Auth;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class LogoutController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Api\V1\Mobile\AuthController::class;
    }

    protected static function targetMethod(): string
    {
        return "logout";
    }
}
