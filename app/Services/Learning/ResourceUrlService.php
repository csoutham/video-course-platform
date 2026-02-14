<?php

namespace App\Services\Learning;

use App\Models\LessonResource;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class ResourceUrlService
{
    public function signedDownloadUrl(LessonResource $resource, User $user): string
    {
        return URL::temporarySignedRoute(
            'resources.stream',
            now()->addMinutes(5),
            [
                'resource' => $resource->id,
                'user' => $user->id,
            ]
        );
    }
}
