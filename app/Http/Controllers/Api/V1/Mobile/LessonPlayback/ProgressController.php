<?php

namespace App\Http\Controllers\Api\V1\Mobile\LessonPlayback;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class ProgressController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Api\V1\Mobile\LessonPlaybackController::class;
    }

    protected static function targetMethod(): string
    {
        return "progress";
    }
}
