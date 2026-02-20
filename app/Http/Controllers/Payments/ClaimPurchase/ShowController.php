<?php

namespace App\Http\Controllers\Payments\ClaimPurchase;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class ShowController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Payments\ClaimPurchaseController::class;
    }

    protected static function targetMethod(): string
    {
        return "show";
    }
}
