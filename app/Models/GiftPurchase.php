<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GiftPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'course_id',
        'buyer_user_id',
        'buyer_email',
        'recipient_email',
        'recipient_name',
        'gift_message',
        'status',
        'delivered_at',
        'claimed_by_user_id',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function buyerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function claimedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function claimToken(): HasOne
    {
        return $this->hasOne(PurchaseClaimToken::class, 'gift_purchase_id');
    }
}

