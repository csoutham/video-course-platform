<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\LessonResource;
use App\Services\Audit\AuditLogService;
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
        AuditLogService $auditLogService,
    ): RedirectResponse {
        if (! $accessService->userCanAccessResource($request->user(), $resource)) {
            $auditLogService->record(
                eventType: 'resource_download_denied',
                userId: $request->user()->id,
                context: ['resource_id' => $resource->id]
            );
            abort(403);
        }

        $auditLogService->record(
            eventType: 'resource_download_requested',
            userId: $request->user()->id,
            context: ['resource_id' => $resource->id]
        );

        return redirect()->away($resourceUrlService->signedDownloadUrl($resource, $request->user()));
    }

    public function stream(
        Request $request,
        LessonResource $resource,
        CourseAccessService $accessService,
        AuditLogService $auditLogService,
    ) {
        if ((int) $request->query('user') !== (int) $request->user()->id) {
            $auditLogService->record(
                eventType: 'resource_stream_denied_user_mismatch',
                userId: $request->user()->id,
                context: ['resource_id' => $resource->id]
            );
            abort(403);
        }

        if (! $accessService->userCanAccessResource($request->user(), $resource)) {
            $auditLogService->record(
                eventType: 'resource_stream_denied',
                userId: $request->user()->id,
                context: ['resource_id' => $resource->id]
            );
            abort(403);
        }

        $disk = config('filesystems.course_resources_disk', 'local');

        if (! Storage::disk($disk)->exists($resource->storage_key)) {
            abort(404);
        }

        $auditLogService->record(
            eventType: 'resource_stream_served',
            userId: $request->user()->id,
            context: ['resource_id' => $resource->id]
        );

        return Storage::disk($disk)->download(
            $resource->storage_key,
            $resource->name
        );
    }
}
