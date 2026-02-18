<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Api\V1\Mobile\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use App\Models\LessonResource;
use App\Models\User;
use App\Services\Learning\CourseAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ResourceAccessController extends Controller
{
    use RespondsWithApiErrors;

    public function show(
        Request $request,
        LessonResource $resource,
        CourseAccessService $accessService,
    ): JsonResponse {
        if (! $accessService->userCanAccessResource($request->user(), $resource)) {
            return $this->errorResponse('resource_forbidden', 'You do not have access to this resource.', 403);
        }

        $expiresAt = now()->addMinutes(5);

        $url = URL::temporarySignedRoute(
            'api.v1.mobile.resources.file',
            $expiresAt,
            [
                'resource' => $resource->id,
                'user' => $request->user()->id,
            ]
        );

        return response()->json([
            'resource' => [
                'id' => $resource->id,
                'name' => $resource->name,
                'mime_type' => $resource->mime_type,
                'size_bytes' => $resource->size_bytes,
                'expires_at' => $expiresAt->toIso8601String(),
                'url' => $url,
            ],
        ]);
    }

    public function file(
        Request $request,
        LessonResource $resource,
        CourseAccessService $accessService,
    ) {
        $user = User::query()->find((int) $request->query('user'));

        if (! $user || ! $accessService->userCanAccessResource($user, $resource)) {
            return $this->errorResponse('resource_forbidden', 'You do not have access to this resource.', 403);
        }

        $disk = config('filesystems.course_resources_disk', 'local');

        if (! Storage::disk($disk)->exists($resource->storage_key)) {
            return $this->errorResponse('resource_not_found', 'Resource file not found.', 404);
        }

        return Storage::disk($disk)->download($resource->storage_key, $resource->name);
    }
}
