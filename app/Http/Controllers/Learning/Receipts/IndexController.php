<?php

namespace App\Http\Controllers\Learning\Receipts;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class IndexController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Learning\ReceiptsController::class;
    }

    protected static function targetMethod(): string
    {
        return "index";
    }
}
