<?php

namespace App\Services\Payments;

use App\Models\Entitlement;
use App\Models\Order;
use RuntimeException;

class EntitlementService
{
    public function grantForOrder(Order $order): void
    {
        if (! $order->user_id) {
            throw new RuntimeException('Cannot grant entitlements without an order user.');
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            Entitlement::query()->updateOrCreate(
                [
                    'user_id' => $order->user_id,
                    'course_id' => $item->course_id,
                ],
                [
                    'order_id' => $order->id,
                    'status' => 'active',
                    'granted_at' => now(),
                    'revoked_at' => null,
                ]
            );
        }
    }

    public function revokeForOrder(Order $order): void
    {
        Entitlement::query()->where('order_id', $order->id)->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
