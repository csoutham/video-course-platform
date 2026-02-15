<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseClaimToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gift_purchase_id',
        'purpose',
        'token',
        'expires_at',
        'consumed_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function giftPurchase(): BelongsTo
    {
        return $this->belongsTo(GiftPurchase::class);
    }
}
