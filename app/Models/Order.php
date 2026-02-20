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
        'order_type',
        'subscription_id',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'paid_at',
        'refunded_at',
    ];

    #[\Override]
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

    #[\Override]
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isStripeReceiptEligible(): bool
    {
        if ((int) $this->total_amount <= 0) {
            return false;
        }

        if (is_string($this->stripe_payment_intent_id) && $this->stripe_payment_intent_id !== '') {
            return true;
        }

        if (! is_string($this->stripe_checkout_session_id) || $this->stripe_checkout_session_id === '') {
            return false;
        }

        return str_starts_with($this->stripe_checkout_session_id, 'cs_');
    }
}
