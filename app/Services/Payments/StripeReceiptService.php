<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripeReceiptService
{
    public function ensureReceiptUrl(Order $order): ?string
    {
        if ($order->stripe_receipt_url) {
            return $order->stripe_receipt_url;
        }

        try {
            $stripe = new StripeClient((string) config('services.stripe.secret'));
            $paymentIntentId = $order->stripe_payment_intent_id;

            if (! $paymentIntentId && $order->stripe_checkout_session_id) {
                $checkoutSession = $stripe->checkout->sessions->retrieve($order->stripe_checkout_session_id);
                $sessionPaymentIntent = $checkoutSession->payment_intent;

                if (is_string($sessionPaymentIntent) && $sessionPaymentIntent !== '') {
                    $paymentIntentId = $sessionPaymentIntent;
                    $order->forceFill([
                        'stripe_payment_intent_id' => $paymentIntentId,
                    ])->save();
                }
            }

            if (! is_string($paymentIntentId) || $paymentIntentId === '') {
                return null;
            }

            $paymentIntent = $stripe->paymentIntents->retrieve(
                $paymentIntentId,
                ['expand' => ['latest_charge']]
            );

            $receiptUrl = is_object($paymentIntent->latest_charge)
                ? ($paymentIntent->latest_charge->receipt_url ?? null)
                : null;

            if (! is_string($receiptUrl) || $receiptUrl === '') {
                return null;
            }

            $order->forceFill([
                'stripe_receipt_url' => $receiptUrl,
            ])->save();

            return $receiptUrl;
        } catch (\Throwable $exception) {
            Log::warning('stripe_receipt_lookup_failed', [
                'order_id' => $order->id,
                'payment_intent_id' => $order->stripe_payment_intent_id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
