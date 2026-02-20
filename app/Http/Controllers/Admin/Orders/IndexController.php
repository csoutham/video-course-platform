<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class IndexController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\OrdersController::class;
    }

    protected static function targetMethod(): string
    {
        return "index";
    }
}
