<?php

namespace App\Services\Claims;

use App\Models\Order;
use App\Models\PurchaseClaimToken;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class PurchaseClaimService
{
    public function issueForOrder(Order $order, string $purpose = 'order_claim'): PurchaseClaimToken
    {
        $expiresAt = CarbonImmutable::now()->addDays(7);

        $existing = PurchaseClaimToken::query()->firstWhere('order_id', $order->id);

        if ($existing && ! $existing->consumed_at && $existing->expires_at && $existing->expires_at->isFuture()) {
            return $existing;
        }

        return PurchaseClaimToken::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'purpose' => $purpose,
                'token' => Str::random(64),
                'expires_at' => $expiresAt,
                'consumed_at' => null,
            ]
        );
    }

    public function resolveActiveToken(string $token): ?PurchaseClaimToken
    {
        return PurchaseClaimToken::query()
            ->whereIn('purpose', ['order_claim', 'preorder_claim'])
            ->where('token', $token)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
