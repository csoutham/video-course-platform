<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseReview extends Model
{
    use HasFactory;

    public const SOURCE_NATIVE = 'native';

    public const SOURCE_UDEMY_MANUAL = 'udemy_manual';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'course_id',
        'user_id',
        'source',
        'reviewer_name',
        'rating',
        'title',
        'body',
        'status',
        'original_reviewed_at',
        'last_submitted_at',
        'approved_at',
        'approved_by_user_id',
        'rejected_at',
        'rejected_by_user_id',
        'hidden_at',
        'hidden_by_user_id',
        'moderation_note',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'original_reviewed_at' => 'datetime',
            'last_submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function hiddenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by_user_id');
    }

    protected function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    protected function getPublicReviewerNameAttribute(): string
    {
        if ($this->source === self::SOURCE_UDEMY_MANUAL) {
            return (string) ($this->reviewer_name ?: 'Learner');
        }

        $name = trim((string) ($this->user?->name ?? ''));
        if ($name === '') {
            return 'Learner';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $first = (string) ($parts[0] ?? '');
        $last = (string) ($parts[count($parts) - 1] ?? '');
        $lastInitial = $last !== '' ? strtoupper(mb_substr($last, 0, 1)).'.' : '';

        return trim($first.' '.$lastInitial);
    }

    protected function getDisplayDateAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->original_reviewed_at ?? $this->approved_at ?? $this->created_at;
    }
}

