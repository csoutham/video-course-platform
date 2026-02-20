<?php

namespace App\Http\Controllers\Api\V1\Mobile\Auth;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class LoginController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Api\V1\Mobile\AuthController::class;
    }

    protected static function targetMethod(): string
    {
        return "login";
    }
}
