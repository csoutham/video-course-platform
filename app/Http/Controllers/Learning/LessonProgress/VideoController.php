<?php

namespace App\Http\Controllers\Learning\LessonProgress;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class VideoController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Learning\LessonProgressController::class;
    }

    protected static function targetMethod(): string
    {
        return "video";
    }
}
