<?php

namespace App\Services\Gifts;

use App\Mail\GiftDeliveryMail;
use App\Mail\GiftPurchaseConfirmationMail;
use App\Models\GiftPurchase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GiftNotificationService
{
    public function sendGiftEmails(GiftPurchase $giftPurchase, string $claimUrl): void
    {
        try {
            Mail::to($giftPurchase->recipient_email)->send(new GiftDeliveryMail(
                giftPurchase: $giftPurchase,
                claimUrl: $claimUrl,
            ));

            Mail::to($giftPurchase->buyer_email)->send(new GiftPurchaseConfirmationMail(
                giftPurchase: $giftPurchase,
                claimUrl: $claimUrl,
            ));
        } catch (\Throwable $exception) {
            Log::warning('gift_notification_email_failed', [
                'gift_purchase_id' => $giftPurchase->id,
                'order_id' => $giftPurchase->order_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
