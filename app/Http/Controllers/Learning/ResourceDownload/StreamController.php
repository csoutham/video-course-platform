<?php

namespace App\Http\Controllers\Learning\ResourceDownload;

use App\Http\Controllers\Concerns\InvokesControllerAction;

class StreamController
{
    use InvokesControllerAction;

    protected static function targetClass(): string
    {
        return \App\Http\Controllers\Learning\ResourceDownloadController::class;
    }

    protected static function targetMethod(): string
    {
        return "stream";
    }
}
