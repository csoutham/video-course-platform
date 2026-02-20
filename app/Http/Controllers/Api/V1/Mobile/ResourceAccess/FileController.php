<?php

namespace App\Http\Controllers\Api\V1\Mobile\ResourceAccess;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class FileController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Api\V1\Mobile\ResourceAccessController::class;
    }

    protected static function targetMethod(): string
    {
        return "file";
    }
}
