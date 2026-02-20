<?php

namespace App\Http\Controllers\Gifts\GiftClaim;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class StoreController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Gifts\GiftClaimController::class;
    }

    protected static function targetMethod(): string
    {
        return "store";
    }
}
