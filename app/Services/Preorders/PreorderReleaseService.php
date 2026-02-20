<?php

namespace App\Services\Preorders;

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PreorderReservation;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Claims\PurchaseClaimService;
use App\Services\Payments\EntitlementService;
use App\Services\Payments\PurchaseReceiptService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class PreorderReleaseService
{
    public function __construct(
        private readonly PreorderCheckoutService $preorderCheckoutService,
        private readonly EntitlementService $entitlementService,
        private readonly PurchaseClaimService $purchaseClaimService,
        private readonly PurchaseReceiptService $purchaseReceiptService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function reserveFromCheckoutSession(array $session): ?PreorderReservation
    {
        $courseId = (int) Arr::get($session, 'metadata.course_id');
        if ($courseId <= 0) {
            return null;
        }

        $course = Course::query()->find($courseId);
        if (! $course || ! $course->is_preorder_enabled) {
            return null;
        }

        $setupIntentId = (string) Arr::get($session, 'setup_intent');
        if ($setupIntentId === '') {
            return null;
        }

        $paymentMethodId = $this->preorderCheckoutService->resolveSetupIntentPaymentMethod($setupIntentId);
        if (! $paymentMethodId) {
            return null;
        }

        $email = (string) (Arr::get($session, 'customer_details.email')
            ?: Arr::get($session, 'customer_email')
            ?: Arr::get($session, 'metadata.customer_email')
            ?: '');

        if ($email === '') {
            return null;
        }

        $userId = (int) Arr::get($session, 'metadata.user_id');
        $user = $userId > 0 ? User::query()->find($userId) : null;

        $reservation = PreorderReservation::query()->updateOrCreate(
            ['stripe_setup_intent_id' => $setupIntentId],
            [
                'course_id' => $course->id,
                'user_id' => $user?->id,
                'email' => $email,
                'stripe_customer_id' => (string) Arr::get($session, 'customer'),
                'stripe_payment_method_id' => $paymentMethodId,
                'reserved_price_amount' => (int) ($course->preorder_price_amount ?? 0),
                'currency' => strtolower((string) $course->price_currency),
                'status' => 'reserved',
                'release_at' => $course->release_at,
            ]
        );

        $this->auditLogService->record(
            eventType: 'preorder_reserved',
            userId: $reservation->user_id,
            context: [
                'preorder_reservation_id' => $reservation->id,
                'course_id' => $course->id,
                'email' => $reservation->email,
            ]
        );

        return $reservation;
    }

    /**
     * @return array{processed:int,charged:int,failed:int}
     */
    public function releaseDueReservations(): array
    {
        $result = [
            'processed' => 0,
            'charged' => 0,
            'failed' => 0,
        ];

        $reservations = PreorderReservation::query()
            ->where('status', 'reserved')
            ->where('release_at', '<=', now())
            ->orderBy('id')
            ->get();

        foreach ($reservations as $reservation) {
            $result['processed']++;

            if ($this->chargeReservation($reservation->fresh())) {
                $result['charged']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    private function chargeReservation(PreorderReservation $reservation): bool
    {
        $course = $reservation->course()->firstOrFail();

        if (! $course->is_published) {
            $course->forceFill(['is_published' => true])->save();
        }

        if ((int) $reservation->reserved_price_amount <= 0 || ! $reservation->stripe_payment_method_id || ! $reservation->stripe_customer_id) {
            $reservation->forceFill([
                'status' => 'failed',
                'failure_code' => 'invalid_preorder_state',
                'failure_message' => 'Reservation is missing required payment data.',
            ])->save();

            return false;
        }

        $stripe = new StripeClient((string) config('services.stripe.secret'));

        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => (int) $reservation->reserved_price_amount,
                'currency' => strtolower((string) $reservation->currency),
                'customer' => $reservation->stripe_customer_id,
                'payment_method' => $reservation->stripe_payment_method_id,
                'confirm' => true,
                'off_session' => true,
                'metadata' => [
                    'flow' => 'preorder_charge',
                    'preorder_reservation_id' => (string) $reservation->id,
                    'course_id' => (string) $reservation->course_id,
                    'email' => $reservation->email,
                    'source' => 'videocourses-release-command',
                ],
            ]);
        } catch (ApiErrorException $exception) {
            $reservation->forceFill([
                'status' => 'failed',
                'failure_code' => $exception->getStripeCode(),
                'failure_message' => $exception->getMessage(),
            ])->save();

            return false;
        }

        if ((string) $paymentIntent->status !== 'succeeded') {
            $reservation->forceFill([
                'status' => 'action_required',
                'stripe_payment_intent_id' => (string) $paymentIntent->id,
                'failure_code' => 'payment_intent_'.$paymentIntent->status,
                'failure_message' => 'Charge requires customer action.',
            ])->save();

            return false;
        }

        DB::transaction(function () use ($reservation, $paymentIntent): void {
            $course = $reservation->course()->firstOrFail();

            $order = Order::query()->updateOrCreate(
                ['stripe_checkout_session_id' => 'preorder_'.$reservation->id],
                [
                    'user_id' => $reservation->user_id,
                    'email' => $reservation->email,
                    'stripe_customer_id' => $reservation->stripe_customer_id,
                    'stripe_payment_intent_id' => (string) $paymentIntent->id,
                    'status' => 'paid',
                    'order_type' => 'preorder',
                    'subtotal_amount' => (int) $reservation->reserved_price_amount,
                    'discount_amount' => 0,
                    'total_amount' => (int) $reservation->reserved_price_amount,
                    'currency' => strtolower((string) $reservation->currency),
                    'paid_at' => now(),
                ]
            );

            OrderItem::query()->updateOrCreate(
                [
                    'order_id' => $order->id,
                    'course_id' => $course->id,
                ],
                [
                    'unit_amount' => (int) $reservation->reserved_price_amount,
                    'quantity' => 1,
                ]
            );

            $claimUrl = null;

            if ($reservation->user_id) {
                $this->entitlementService->grantForOrder($order);
            } else {
                $claimToken = $this->purchaseClaimService->issueForOrder($order, 'preorder_claim');
                $claimUrl = URL::route('claim-purchase.show', $claimToken->token);
            }

            $reservation->forceFill([
                'status' => 'charged',
                'charged_order_id' => $order->id,
                'stripe_payment_intent_id' => (string) $paymentIntent->id,
                'charged_at' => now(),
                'failure_code' => null,
                'failure_message' => null,
            ])->save();

            DB::afterCommit(function () use ($order, $claimUrl, $reservation): void {
                $this->purchaseReceiptService->sendPaidReceipt($order->fresh('items.course'), $claimUrl);

                $this->auditLogService->record(
                    eventType: 'preorder_charge_succeeded',
                    userId: $reservation->user_id,
                    context: [
                        'preorder_reservation_id' => $reservation->id,
                        'order_id' => $order->id,
                        'course_id' => $reservation->course_id,
                        'email' => $reservation->email,
                    ]
                );
            });
        });

        return true;
    }
}
