<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Arr;

class SubscriptionSyncService
{
    public function syncFromStripeSubscription(array $payload): Subscription
    {
        $stripeSubscriptionId = (string) Arr::get($payload, 'id');

        throw_if($stripeSubscriptionId === '', \InvalidArgumentException::class, 'Missing stripe subscription id.');

        $customerId = (string) Arr::get($payload, 'customer');
        $status = (string) Arr::get($payload, 'status', 'incomplete');
        $priceId = (string) Arr::get($payload, 'items.data.0.price.id', '');
        $interval = (string) Arr::get($payload, 'items.data.0.price.recurring.interval', 'month');

        $userId = Arr::get($payload, 'metadata.user_id');
        $email = (string) (Arr::get($payload, 'metadata.customer_email') ?: '');

        $user = null;
        if (is_numeric($userId)) {
            $user = User::query()->find((int) $userId);
        }

        if (! $user && $customerId !== '') {
            $user = User::query()
                ->whereHas('orders', fn ($query) => $query->where('stripe_customer_id', $customerId))
                ->first();
        }

        if (! $user && $email !== '') {
            $user = User::query()->firstWhere('email', $email);
        }

        if ($email === '' && $user?->email) {
            $email = $user->email;
        }

        $subscription = Subscription::query()->updateOrCreate(
            ['stripe_subscription_id' => $stripeSubscriptionId],
            [
                'user_id' => $user?->id,
                'email' => $email,
                'stripe_customer_id' => $customerId,
                'stripe_price_id' => $priceId,
                'interval' => $interval === 'year' ? 'yearly' : 'monthly',
                'status' => $status,
                'current_period_start' => $this->timestampToDateTime(Arr::get($payload, 'current_period_start')),
                'current_period_end' => $this->timestampToDateTime(Arr::get($payload, 'current_period_end')),
                'cancel_at_period_end' => (bool) Arr::get($payload, 'cancel_at_period_end', false),
                'canceled_at' => $this->timestampToDateTime(Arr::get($payload, 'canceled_at')),
                'ended_at' => $this->timestampToDateTime(Arr::get($payload, 'ended_at')),
            ]
        );

        return $subscription;
    }

    public function recordInvoice(array $invoicePayload, bool $isPaid): ?Order
    {
        $invoiceId = (string) Arr::get($invoicePayload, 'id');
        $subscriptionId = (string) Arr::get($invoicePayload, 'subscription');

        if ($invoiceId === '' || $subscriptionId === '') {
            return null;
        }

        $subscription = Subscription::query()->firstWhere('stripe_subscription_id', $subscriptionId);

        if (! $subscription) {
            return null;
        }

        if ($isPaid) {
            $subscription->forceFill([
                'status' => (string) Arr::get($invoicePayload, 'subscription_details.metadata.status', $subscription->status),
                'current_period_start' => $this->timestampToDateTime(Arr::get($invoicePayload, 'lines.data.0.period.start')),
                'current_period_end' => $this->timestampToDateTime(Arr::get($invoicePayload, 'lines.data.0.period.end')),
            ])->save();
        } else {
            $subscription->forceFill([
                'status' => 'past_due',
            ])->save();
        }

        return Order::query()->updateOrCreate(
            ['stripe_checkout_session_id' => 'subinv_'.$invoiceId],
            [
                'user_id' => $subscription->user_id,
                'email' => $subscription->email,
                'stripe_customer_id' => $subscription->stripe_customer_id,
                'stripe_payment_intent_id' => (string) Arr::get($invoicePayload, 'payment_intent', ''),
                'status' => $isPaid ? 'paid' : 'failed',
                'order_type' => 'subscription',
                'subscription_id' => $subscription->id,
                'subtotal_amount' => (int) Arr::get($invoicePayload, 'subtotal', 0),
                'discount_amount' => max(0, (int) Arr::get($invoicePayload, 'subtotal', 0) - (int) Arr::get($invoicePayload, 'amount_paid', Arr::get($invoicePayload, 'total', 0))),
                'total_amount' => (int) ($isPaid
                    ? Arr::get($invoicePayload, 'amount_paid', Arr::get($invoicePayload, 'total', 0))
                    : Arr::get($invoicePayload, 'total', 0)),
                'currency' => strtolower((string) Arr::get($invoicePayload, 'currency', 'usd')),
                'paid_at' => $isPaid ? now() : null,
            ]
        );
    }

    private function timestampToDateTime(mixed $value): ?\Illuminate\Support\Carbon
    {
        if (! is_numeric($value)) {
            return null;
        }

        $timestamp = (int) $value;

        return $timestamp > 0 ? now()->setTimestamp($timestamp) : null;
    }
}
