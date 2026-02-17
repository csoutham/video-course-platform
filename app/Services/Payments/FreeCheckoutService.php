<?php

namespace App\Services\Payments;

use App\Models\Course;
use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\User;
use App\Services\Claims\GiftClaimService;
use App\Services\Claims\PurchaseClaimService;
use App\Services\Gifts\GiftNotificationService;
use App\Services\Marketing\KitAudienceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class FreeCheckoutService
{
    public function __construct(
        private readonly PurchaseClaimService $purchaseClaimService,
        private readonly GiftClaimService $giftClaimService,
        private readonly GiftNotificationService $giftNotificationService,
        private readonly EntitlementService $entitlementService,
        private readonly PurchaseReceiptService $purchaseReceiptService,
        private readonly KitAudienceService $kitAudienceService,
    ) {
    }

    /**
     * @return array{session_id: string, order_id: int}
     */
    public function complete(
        Course $course,
        ?User $user,
        string $customerEmail,
        bool $isGift = false,
        ?string $recipientEmail = null,
        ?string $recipientName = null,
        ?string $giftMessage = null,
    ): array {
        $sessionId = 'free_'.strtolower((string) Str::ulid());

        $order = DB::transaction(function () use (
            $course,
            $user,
            $customerEmail,
            $isGift,
            $recipientEmail,
            $recipientName,
            $giftMessage,
            $sessionId,
        ): Order {
            $order = Order::query()->create([
                'user_id' => $isGift ? null : $user?->id,
                'email' => $customerEmail,
                'stripe_checkout_session_id' => $sessionId,
                'status' => 'paid',
                'subtotal_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'currency' => $course->price_currency ?: 'usd',
                'paid_at' => now(),
            ]);

            $order->items()->create([
                'course_id' => $course->id,
                'unit_amount' => 0,
                'quantity' => 1,
            ]);

            if ($isGift) {
                $giftPurchase = GiftPurchase::query()->create([
                    'order_id' => $order->id,
                    'course_id' => $course->id,
                    'buyer_user_id' => $user?->id,
                    'buyer_email' => $customerEmail,
                    'recipient_email' => (string) $recipientEmail,
                    'recipient_name' => $recipientName ?: null,
                    'gift_message' => $giftMessage ?: null,
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);

                $giftToken = $this->giftClaimService->issueForGift($giftPurchase);
                $giftClaimUrl = URL::route('gift-claim.show', $giftToken->token);

                DB::afterCommit(function () use ($order, $giftPurchase, $giftClaimUrl): void {
                    $this->kitAudienceService->syncPurchaser($order->fresh(['user', 'items.course']));
                    $this->purchaseReceiptService->sendPaidReceipt($order->fresh('items.course'));
                    $this->giftNotificationService->sendGiftEmails($giftPurchase->fresh('course'), $giftClaimUrl);
                });

                return $order;
            }

            $claimUrl = null;
            if ($user && $course->free_access_mode === 'direct') {
                $this->entitlementService->grantForOrder($order->fresh('items'));
            } else {
                $claimToken = $this->purchaseClaimService->issueForOrder($order);
                $claimUrl = URL::route('claim-purchase.show', $claimToken->token);
            }

            DB::afterCommit(function () use ($order, $claimUrl): void {
                $this->kitAudienceService->syncPurchaser($order->fresh(['user', 'items.course']));
                $this->purchaseReceiptService->sendPaidReceipt($order->fresh('items.course'), $claimUrl);
            });

            return $order;
        });

        return [
            'session_id' => $sessionId,
            'order_id' => $order->id,
        ];
    }
}
