<?php

namespace App\Services\Payments;

use App\Models\Entitlement;
use App\Models\Order;
use App\Services\Audit\AuditLogService;
use RuntimeException;

class EntitlementService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function grantForOrder(Order $order): void
    {
        throw_unless($order->user_id, RuntimeException::class, 'Cannot grant entitlements without an order user.');

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

            $this->auditLogService->record(
                eventType: 'entitlement_granted',
                userId: $order->user_id,
                context: [
                    'order_id' => $order->id,
                    'course_id' => $item->course_id,
                ]
            );
        }
    }

    public function revokeForOrder(Order $order): void
    {
        $entitlements = Entitlement::query()->where('order_id', $order->id)->get();

        Entitlement::query()->where('order_id', $order->id)->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($entitlements as $entitlement) {
            $this->auditLogService->record(
                eventType: 'entitlement_revoked',
                userId: $entitlement->user_id,
                context: [
                    'order_id' => $order->id,
                    'course_id' => $entitlement->course_id,
                ]
            );
        }
    }
}
