<?php

namespace App\Http\Controllers\Payments\ClaimPurchase;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class StoreController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Payments\ClaimPurchaseController::class;
    }

    protected static function targetMethod(): string
    {
        return "store";
    }
}
