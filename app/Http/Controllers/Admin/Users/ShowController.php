<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class ShowController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\UsersController::class;
    }

    protected static function targetMethod(): string
    {
        return "show";
    }
}
