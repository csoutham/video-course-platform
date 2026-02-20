<?php

namespace App\Http\Controllers\Admin\Imports\Udemy;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class CommitController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Admin\UdemyImportsController::class;
    }

    protected static function targetMethod(): string
    {
        return "commit";
    }
}
