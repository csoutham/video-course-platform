<?php

namespace App\Http\Resources\Api\V1\Mobile;

use App\Models\CourseLesson;
use App\Models\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Course */
class MobileCourseDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progressByLessonId = $this->resource->mobile_progress_by_lesson_id ?? collect();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'long_description' => $this->long_description,
            'thumbnail_url' => $this->thumbnail_url,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'modules' => $this->modules->map(function ($module) use ($progressByLessonId) {
                return [
                    'id' => $module->id,
                    'title' => $module->title,
                    'sort_order' => $module->sort_order,
                    'updated_at' => $module->updated_at?->toIso8601String(),
                    'lessons' => $module->lessons->map(function (CourseLesson $lesson) use ($progressByLessonId) {
                        $progress = $progressByLessonId->get($lesson->id);

                        return [
                            'id' => $lesson->id,
                            'slug' => $lesson->slug,
                            'title' => $lesson->title,
                            'summary' => $lesson->summary,
                            'duration_seconds' => $lesson->duration_seconds,
                            'stream_video_id' => $lesson->stream_video_id,
                            'has_video' => $lesson->stream_video_id !== null && $lesson->stream_video_id !== '',
                            'sort_order' => $lesson->sort_order,
                            'updated_at' => $lesson->updated_at?->toIso8601String(),
                            'progress' => $progress
                                ? (new MobileLessonProgressResource($progress))->resolve()
                                : null,
                            'resources' => $lesson->resources->map(function (LessonResource $resource) {
                                return [
                                    'id' => $resource->id,
                                    'name' => $resource->name,
                                    'mime_type' => $resource->mime_type,
                                    'size_bytes' => $resource->size_bytes,
                                    'updated_at' => $resource->updated_at?->toIso8601String(),
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }
}
