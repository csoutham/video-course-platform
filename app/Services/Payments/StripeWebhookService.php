<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StripeEvent;
use App\Models\User;
use App\Services\Claims\PurchaseClaimService;
use Illuminate\Support\Arr;
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
    ) {}

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
        });
    }

    private function processEvent(Event $event): void
    {
        $object = $event->data->object->toArray();

        match ($event->type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->markOrderPaid($object),
            'checkout.session.async_payment_failed' => $this->markOrderFailed($object),
            'charge.refunded' => $this->markOrderRefunded($object),
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

        $order = Order::query()->updateOrCreate(
            ['stripe_checkout_session_id' => $sessionId],
            [
                'user_id' => $user?->id,
                'email' => $email,
                'stripe_customer_id' => Arr::get($session, 'customer') ?: null,
                'status' => 'paid',
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

        if (! $order->user_id) {
            $claimToken = $this->purchaseClaimService->issueForOrder($order);

            Log::info('guest_purchase_claim_token_issued', [
                'order_id' => $order->id,
                'email' => $order->email,
                'claim_url' => URL::route('claim-purchase.show', $claimToken->token),
            ]);

            return;
        }

        $this->entitlementService->grantForOrder($order);
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

        $order->forceFill([
            'status' => 'refunded',
            'refunded_at' => now(),
        ])->save();

        $this->entitlementService->revokeForOrder($order);
    }
}
