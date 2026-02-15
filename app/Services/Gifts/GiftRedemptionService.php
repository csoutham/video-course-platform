<?php

namespace App\Services\Gifts;

use App\Models\Entitlement;
use App\Models\PurchaseClaimToken;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GiftRedemptionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function redeem(PurchaseClaimToken $claimToken, User $user): void
    {
        $giftPurchase = $claimToken->giftPurchase;

        if (! $giftPurchase) {
            throw ValidationException::withMessages([
                'claim' => 'Gift could not be found.',
            ]);
        }

        if ($giftPurchase->status === 'claimed' || $giftPurchase->claimed_at) {
            throw ValidationException::withMessages([
                'claim' => 'This gift has already been claimed.',
            ]);
        }

        if ($giftPurchase->status === 'revoked') {
            throw ValidationException::withMessages([
                'claim' => 'This gift is no longer redeemable.',
            ]);
        }

        if (Entitlement::query()->active()->where('user_id', $user->id)->where('course_id', $giftPurchase->course_id)->exists()) {
            throw ValidationException::withMessages([
                'claim' => 'This account already has access to this course.',
            ]);
        }

        DB::transaction(function () use ($claimToken, $giftPurchase, $user): void {
            Entitlement::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $giftPurchase->course_id,
                ],
                [
                    'order_id' => $giftPurchase->order_id,
                    'status' => 'active',
                    'granted_at' => now(),
                    'revoked_at' => null,
                ]
            );

            $giftPurchase->forceFill([
                'status' => 'claimed',
                'claimed_by_user_id' => $user->id,
                'claimed_at' => now(),
            ])->save();

            $claimToken->forceFill([
                'consumed_at' => now(),
            ])->save();

            $this->auditLogService->record(
                eventType: 'gift_claim_completed',
                userId: $user->id,
                context: [
                    'gift_purchase_id' => $giftPurchase->id,
                    'order_id' => $giftPurchase->order_id,
                    'course_id' => $giftPurchase->course_id,
                ]
            );
        });
    }
}

