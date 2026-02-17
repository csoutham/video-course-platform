<?php

namespace App\Services\Payments;

use App\Mail\PurchaseReceiptMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PurchaseReceiptService
{
    public function __construct(
        private readonly StripeReceiptService $stripeReceiptService,
    ) {
    }

    public function sendPaidReceipt(Order $order, ?string $claimUrl = null): void
    {
        if (! $order->email || ! $order->isStripeReceiptEligible()) {
            return;
        }

        try {
            $stripeReceiptUrl = $this->stripeReceiptService->ensureReceiptUrl($order);

            Mail::to($order->email)->send(new PurchaseReceiptMail(
                order: $order,
                claimUrl: $claimUrl,
                stripeReceiptUrl: $stripeReceiptUrl,
            ));
        } catch (\Throwable $exception) {
            Log::warning('purchase_receipt_email_failed', [
                'order_id' => $order->id,
                'email' => $order->email,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
