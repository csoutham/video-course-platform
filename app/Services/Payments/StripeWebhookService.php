<?php

namespace App\Services\Payments;

use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StripeEvent;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Billing\SubscriptionSyncService;
use App\Services\Claims\GiftClaimService;
use App\Services\Claims\PurchaseClaimService;
use App\Services\Gifts\GiftNotificationService;
use App\Services\Marketing\KitAudienceService;
use App\Services\Preorders\PreorderReleaseService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Stripe\Event;
use Stripe\Webhook;

class StripeWebhookService
{
    public function __construct(
        private readonly EntitlementService $entitlementService,
        private readonly PurchaseClaimService $purchaseClaimService,
        private readonly GiftClaimService $giftClaimService,
        private readonly GiftNotificationService $giftNotificationService,
        private readonly PurchaseReceiptService $purchaseReceiptService,
        private readonly KitAudienceService $kitAudienceService,
        private readonly AuditLogService $auditLogService,
        private readonly SubscriptionSyncService $subscriptionSyncService,
        private readonly PreorderReleaseService $preorderReleaseService,
    ) {
    }

    public function handle(string $payload, string $signature): void
    {
        $event = Webhook::constructEvent(
            $payload,
            $signature,
            (string) config('services.stripe.webhook_secret')
        );

        $payloadArray = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($event, $payloadArray): void {
            $stripeEvent = StripeEvent::query()->firstOrCreate(
                ['stripe_event_id' => $event->id],
                [
                    'event_type' => $event->type,
                    'payload_json' => $payloadArray,
                ]
            );

            if ($stripeEvent->processed_at) {
                return;
            }

            $this->processEvent($event);

            $stripeEvent->forceFill([
                'event_type' => $event->type,
                'payload_json' => $payloadArray,
                'processed_at' => now(),
                'processing_error' => null,
            ])->save();

            $this->auditLogService->record(
                eventType: 'stripe_webhook_processed',
                context: [
                    'stripe_event_id' => $event->id,
                    'event_type' => $event->type,
                ]
            );
        });
    }

    private function processEvent(Event $event): void
    {
        $object = $event->data->object->toArray();

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)
            && Arr::get($object, 'metadata.flow') === 'preorder_setup') {
            $this->preorderReleaseService->reserveFromCheckoutSession($object);

            return;
        }

        match ($event->type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->markOrderPaid($object),
            'checkout.session.async_payment_failed' => $this->markOrderFailed($object),
            'charge.refunded' => $this->markOrderRefunded($object),
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->subscriptionSyncService->syncFromStripeSubscription($object),
            'invoice.paid' => $this->subscriptionSyncService->recordInvoice($object, true),
            'invoice.payment_failed' => $this->subscriptionSyncService->recordInvoice($object, false),
            default => null,
        };
    }

    private function markOrderPaid(array $session): void
    {
        $courseId = (int) Arr::get($session, 'metadata.course_id');

        if ($courseId <= 0) {
            return;
        }

        $email = Arr::get($session, 'customer_details.email')
            ?? Arr::get($session, 'customer_email')
            ?? Arr::get($session, 'metadata.customer_email');

        if (! $email) {
            return;
        }

        $sessionId = (string) Arr::get($session, 'id');

        if ($sessionId === '') {
            return;
        }

        $userId = (int) Arr::get($session, 'metadata.user_id');
        $user = $userId > 0 ? User::query()->find($userId) : null;
        $existingOrder = Order::query()->firstWhere('stripe_checkout_session_id', $sessionId);
        $wasAlreadyPaid = $existingOrder?->status === 'paid';

        $paymentIntentId = Arr::get($session, 'payment_intent');

        if (is_array($paymentIntentId)) {
            $paymentIntentId = Arr::get($paymentIntentId, 'id');
        }

        if (! is_string($paymentIntentId) || $paymentIntentId === '') {
            $paymentIntentId = null;
        }

        $order = Order::query()->updateOrCreate(
            ['stripe_checkout_session_id' => $sessionId],
            [
                'user_id' => $user?->id,
                'email' => $email,
                'stripe_customer_id' => Arr::get($session, 'customer') ?: null,
                'stripe_payment_intent_id' => $paymentIntentId,
                'status' => 'paid',
                'order_type' => 'one_time',
                'subtotal_amount' => (int) Arr::get($session, 'amount_subtotal', 0),
                'discount_amount' => max(0, (int) Arr::get($session, 'amount_subtotal', 0) - (int) Arr::get($session, 'amount_total', 0)),
                'total_amount' => (int) Arr::get($session, 'amount_total', 0),
                'currency' => strtolower((string) Arr::get($session, 'currency', 'usd')),
                'paid_at' => now(),
            ]
        );

        OrderItem::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'course_id' => $courseId,
            ],
            [
                'unit_amount' => (int) Arr::get($session, 'amount_total', 0),
                'quantity' => 1,
            ]
        );

        $claimUrl = null;
        $isGift = Arr::get($session, 'metadata.is_gift') === '1';

        if ($isGift) {
            $giftCheckoutContext = Cache::pull('gift-checkout:'.$sessionId);
            $giftPurchase = GiftPurchase::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'course_id' => $courseId,
                    'buyer_user_id' => $user?->id,
                    'buyer_email' => $email,
                    'recipient_email' => (string) (Arr::get($giftCheckoutContext, 'recipient_email')
                        ?? Arr::get($session, 'metadata.recipient_email')
                        ?? $email),
                    'recipient_name' => Arr::get($giftCheckoutContext, 'recipient_name')
                        ?? Arr::get($session, 'metadata.recipient_name'),
                    'gift_message' => Arr::get($giftCheckoutContext, 'gift_message'),
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]
            );

            $giftClaimToken = $this->giftClaimService->issueForGift($giftPurchase);
            $claimUrl = URL::route('gift-claim.show', $giftClaimToken->token);
        } elseif (! $order->user_id) {
            $claimToken = $this->purchaseClaimService->issueForOrder($order);
            $claimUrl = URL::route('claim-purchase.show', $claimToken->token);

            Log::info('guest_purchase_claim_token_issued', [
                'order_id' => $order->id,
                'email' => $order->email,
                'claim_url' => $claimUrl,
            ]);
        } else {
            $this->entitlementService->grantForOrder($order);
        }

        if (! $wasAlreadyPaid) {
            DB::afterCommit(function () use ($order, $claimUrl, $isGift): void {
                $this->kitAudienceService->syncPurchaser($order->fresh(['user', 'items.course']));
                $this->purchaseReceiptService->sendPaidReceipt($order->fresh('items.course'), $claimUrl);

                if ($isGift) {
                    $orderWithGift = $order->fresh('giftPurchase.course');

                    if ($orderWithGift?->giftPurchase) {
                        $this->giftNotificationService->sendGiftEmails($orderWithGift->giftPurchase, (string) $claimUrl);
                    }
                }
            });
        }
    }

    private function markOrderFailed(array $session): void
    {
        $sessionId = (string) Arr::get($session, 'id');

        if ($sessionId === '') {
            return;
        }

        $email = Arr::get($session, 'customer_details.email')
            ?? Arr::get($session, 'customer_email')
            ?? Arr::get($session, 'metadata.customer_email')
            ?? 'guest@example.invalid';

        Order::query()->updateOrCreate(
            ['stripe_checkout_session_id' => $sessionId],
            [
                'email' => $email,
                'status' => 'failed',
                'order_type' => 'one_time',
                'subtotal_amount' => (int) Arr::get($session, 'amount_subtotal', 0),
                'discount_amount' => max(0, (int) Arr::get($session, 'amount_subtotal', 0) - (int) Arr::get($session, 'amount_total', 0)),
                'total_amount' => (int) Arr::get($session, 'amount_total', 0),
                'currency' => strtolower((string) Arr::get($session, 'currency', 'usd')),
            ]
        );
    }

    private function markOrderRefunded(array $charge): void
    {
        $sessionId = Arr::get($charge, 'metadata.checkout_session_id');

        if (! $sessionId) {
            return;
        }

        $order = Order::query()->firstWhere('stripe_checkout_session_id', $sessionId);

        if (! $order) {
            return;
        }

        $chargeAmount = (int) Arr::get($charge, 'amount', 0);
        $amountRefunded = (int) Arr::get($charge, 'amount_refunded', 0);
        $isFullyRefunded = $chargeAmount > 0
            ? $amountRefunded >= $chargeAmount
            : (bool) Arr::get($charge, 'refunded', false);

        $order->forceFill([
            'status' => $isFullyRefunded ? 'refunded' : 'partially_refunded',
            'refunded_at' => now(),
        ])->save();

        if (! $isFullyRefunded) {
            return;
        }

        $this->entitlementService->revokeForOrder($order);

        if ($order->giftPurchase) {
            $order->giftPurchase->forceFill([
                'status' => 'revoked',
            ])->save();
        }
    }

    public function reprocessStoredEvent(StripeEvent $storedEvent): void
    {
        $event = Event::constructFrom($storedEvent->payload_json);

        DB::transaction(function () use ($event, $storedEvent): void {
            $this->processEvent($event);

            $storedEvent->forceFill([
                'event_type' => $event->type,
                'processed_at' => now(),
                'processing_error' => null,
            ])->save();

            $this->auditLogService->record(
                eventType: 'stripe_event_reprocessed',
                context: [
                    'stripe_event_id' => $storedEvent->stripe_event_id,
                    'event_type' => $storedEvent->event_type,
                ]
            );
        });
    }
}
