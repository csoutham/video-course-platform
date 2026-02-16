<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'stripe_checkout_session_id',
        'stripe_customer_id',
        'stripe_payment_intent_id',
        'stripe_receipt_url',
        'status',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'paid_at',
        'refunded_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (! $order->public_id) {
                $order->public_id = 'ord_'.strtolower((string) Str::ulid());
            }
        });
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function purchaseClaimToken(): HasOne
    {
        return $this->hasOne(PurchaseClaimToken::class);
    }

    public function giftPurchase(): HasOne
    {
        return $this->hasOne(GiftPurchase::class);
    }
}
