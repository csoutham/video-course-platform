<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Stripe\StripeClient;

class StripeCustomerResolver
{
    public function resolveOrCreateForUser(User $user): string
    {
        $existing = Subscription::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stripe_customer_id')
            ->value('stripe_customer_id');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $existing = Order::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stripe_customer_id')
            ->orderByDesc('id')
            ->value('stripe_customer_id');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $customer = $stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => (string) $user->id,
                'source' => 'videocourses-web',
            ],
        ]);

        return (string) $customer->id;
    }

    public function resolveForUser(User $user): ?string
    {
        $existing = Subscription::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stripe_customer_id')
            ->orderByDesc('id')
            ->value('stripe_customer_id');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $existing = Order::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stripe_customer_id')
            ->orderByDesc('id')
            ->value('stripe_customer_id');

        return is_string($existing) && $existing !== '' ? $existing : null;
    }
}
