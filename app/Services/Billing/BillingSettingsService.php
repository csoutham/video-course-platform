<?php

namespace App\Services\Billing;

use App\Models\BillingSetting;
use InvalidArgumentException;

class BillingSettingsService
{
    public function current(): BillingSetting
    {
        return BillingSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'subscription_currency' => 'usd',
                'stripe_billing_portal_enabled' => false,
            ]
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): BillingSetting
    {
        $settings = $this->current();

        $settings->forceFill([
            'stripe_subscription_monthly_price_id' => $this->nullableTrim($input['stripe_subscription_monthly_price_id'] ?? null),
            'stripe_subscription_yearly_price_id' => $this->nullableTrim($input['stripe_subscription_yearly_price_id'] ?? null),
            'subscription_currency' => strtolower((string) ($input['subscription_currency'] ?? 'usd')),
            'stripe_billing_portal_enabled' => (bool) ($input['stripe_billing_portal_enabled'] ?? false),
            'stripe_billing_portal_configuration_id' => $this->nullableTrim($input['stripe_billing_portal_configuration_id'] ?? null),
        ])->save();

        return $settings->fresh();
    }

    public function stripePriceIdForInterval(string $interval): string
    {
        $settings = $this->current();

        $priceId = match ($interval) {
            'monthly' => $settings->stripe_subscription_monthly_price_id,
            'yearly' => $settings->stripe_subscription_yearly_price_id,
            default => null,
        };

        if (! is_string($priceId) || $priceId === '') {
            throw new InvalidArgumentException('Subscription pricing is not configured for this interval.');
        }

        return $priceId;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
