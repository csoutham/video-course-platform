<?php

namespace App\Http\Controllers\Gifts\GiftClaim;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class ShowController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Gifts\GiftClaimController::class;
    }

    protected static function targetMethod(): string
    {
        return "show";
    }
}
