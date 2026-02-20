<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'interval',
        'status',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'canceled_at',
        'ended_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'canceled_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAccessActive(): bool
    {
        if (in_array($this->status, ['active', 'trialing'], true)) {
            return true;
        }

        if ($this->status === 'canceled' && $this->current_period_end?->isFuture()) {
            return true;
        }

        return false;
    }
}
