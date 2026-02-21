<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CourseCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'order_id',
        'subscription_id',
        'public_id',
        'verification_code',
        'issued_name',
        'issued_course_title',
        'issued_at',
        'status',
        'revoked_at',
        'revoke_reason',
        'template_version',
    ];

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $certificate): void {
            if (! $certificate->public_id) {
                $certificate->public_id = 'cert_'.strtolower((string) Str::ulid());
            }

            if (! $certificate->verification_code) {
                $certificate->verification_code = strtoupper((string) Str::random(14));
            }
        });
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
