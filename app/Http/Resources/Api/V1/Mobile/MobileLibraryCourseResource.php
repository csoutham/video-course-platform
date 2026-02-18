<?php

namespace App\Http\Resources\Api\V1\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Course */
class MobileLibraryCourseResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail_url' => $this->thumbnail_url,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'progress' => [
                'total_lessons' => (int) ($this->mobile_progress_total_lessons ?? 0),
                'completed_lessons' => (int) ($this->mobile_progress_completed_lessons ?? 0),
                'percent_complete' => (int) ($this->mobile_progress_percent_complete ?? 0),
                'last_viewed_at' => $this->mobile_progress_last_viewed_at?->toIso8601String(),
            ],
        ];
    }
}
