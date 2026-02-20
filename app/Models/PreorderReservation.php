<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreorderReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'email',
        'stripe_customer_id',
        'stripe_setup_intent_id',
        'stripe_payment_method_id',
        'reserved_price_amount',
        'currency',
        'status',
        'release_at',
        'charged_order_id',
        'stripe_payment_intent_id',
        'charged_at',
        'failure_code',
        'failure_message',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'release_at' => 'datetime',
            'charged_at' => 'datetime',
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

    public function chargedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'charged_order_id');
    }
}
