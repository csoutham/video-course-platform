<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'user_id',
    'lesson_id',
    'status',
    'playback_position_seconds',
    'video_duration_seconds',
    'percent_complete',
    'started_at',
    'last_viewed_at',
    'completed_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'lesson_progress')]
class LessonProgress extends Model
{
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }
}
