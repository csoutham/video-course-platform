<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, BillingSettingsService $billingSettingsService): RedirectResponse
    {
        $validated = $request->validate([
            'stripe_subscription_monthly_price_id' => ['nullable', 'string', 'max:255'],
            'stripe_subscription_yearly_price_id' => ['nullable', 'string', 'max:255'],
            'subscription_currency' => ['required', 'string', 'in:usd,gbp'],
            'stripe_billing_portal_enabled' => ['nullable', 'boolean'],
            'stripe_billing_portal_configuration_id' => ['nullable', 'string', 'max:255'],
        ]);

        $billingSettingsService->update($validated);

        return to_route('admin.billing.edit')->with('status', 'Billing settings updated.');
    }
}
