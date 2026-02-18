<?php

namespace App\Http\Resources\Api\V1\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LessonProgress */
class MobileLessonProgressResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'playback_position_seconds' => (int) ($this->playback_position_seconds ?? 0),
            'video_duration_seconds' => $this->video_duration_seconds !== null ? (int) $this->video_duration_seconds : null,
            'percent_complete' => (int) ($this->percent_complete ?? 0),
            'last_viewed_at' => $this->last_viewed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
