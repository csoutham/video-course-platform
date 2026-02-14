<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\LessonResource;
use App\Services\Learning\CourseAccessService;
use App\Services\Learning\ResourceUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResourceDownloadController extends Controller
{
    public function download(
        Request $request,
        LessonResource $resource,
        CourseAccessService $accessService,
        ResourceUrlService $resourceUrlService,
    ): RedirectResponse {
        if (! $accessService->userCanAccessResource($request->user(), $resource)) {
            abort(403);
        }

        return redirect()->away($resourceUrlService->signedDownloadUrl($resource, $request->user()));
    }

    public function stream(
        Request $request,
        LessonResource $resource,
        CourseAccessService $accessService,
    ) {
        if ((int) $request->query('user') !== (int) $request->user()->id) {
            abort(403);
        }

        if (! $accessService->userCanAccessResource($request->user(), $resource)) {
            abort(403);
        }

        $disk = config('filesystems.course_resources_disk', 'local');

        if (! Storage::disk($disk)->exists($resource->storage_key)) {
            abort(404);
        }

        return Storage::disk($disk)->download(
            $resource->storage_key,
            $resource->name
        );
    }
}
