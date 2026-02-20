<?php

namespace App\Services\Billing;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Stripe\StripeClient;

class BillingPortalService
{
    public function __construct(
        private readonly BillingSettingsService $billingSettingsService,
        private readonly StripeCustomerResolver $customerResolver,
    ) {
    }

    public function createPortalUrl(User $user): string
    {
        $settings = $this->billingSettingsService->current();

        throw_unless($settings->stripe_billing_portal_enabled, InvalidArgumentException::class, 'Billing portal is not enabled yet.');

        $customerId = $this->customerResolver->resolveForUser($user);

        throw_unless($customerId, InvalidArgumentException::class, 'No Stripe customer found for your account yet.');

        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $params = [
            'customer' => $customerId,
            'return_url' => URL::route('billing.show'),
        ];

        if (is_string($settings->stripe_billing_portal_configuration_id) && $settings->stripe_billing_portal_configuration_id !== '') {
            $params['configuration'] = $settings->stripe_billing_portal_configuration_id;
        }

        $session = $stripe->billingPortal->sessions->create($params);

        return (string) $session->url;
    }
}
