<?php

namespace App\Services\Claims;

use App\Models\GiftPurchase;
use App\Models\PurchaseClaimToken;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class GiftClaimService
{
    public function issueForGift(GiftPurchase $giftPurchase): PurchaseClaimToken
    {
        $expiresAt = CarbonImmutable::now()->addDays(7);

        $existing = PurchaseClaimToken::query()->firstWhere('gift_purchase_id', $giftPurchase->id);

        if ($existing && ! $existing->consumed_at && $existing->expires_at && $existing->expires_at->isFuture()) {
            return $existing;
        }

        return PurchaseClaimToken::query()->updateOrCreate(
            ['gift_purchase_id' => $giftPurchase->id],
            [
                'order_id' => $giftPurchase->order_id,
                'purpose' => 'gift_claim',
                'token' => Str::random(64),
                'expires_at' => $expiresAt,
                'consumed_at' => null,
            ]
        );
    }

    public function resolveActiveGiftToken(string $token): ?PurchaseClaimToken
    {
        return PurchaseClaimToken::query()
            ->where('purpose', 'gift_claim')
            ->where('token', $token)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}

