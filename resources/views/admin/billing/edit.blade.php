<x-admin-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Billing Settings">
    <section class="vc-panel p-6">
        <div class="vc-heading-block">
            <p class="vc-eyebrow">Admin</p>
            <h1 class="vc-title">Billing Settings</h1>
            <p class="vc-subtitle">Configure subscription prices and Stripe Billing Portal behavior.</p>
        </div>
    </section>

    <section class="vc-panel mt-6 p-6">
        <form method="POST" action="{{ route('admin.billing.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="stripe_subscription_monthly_price_id" class="vc-label">Monthly price ID</label>
                    <input
                        id="stripe_subscription_monthly_price_id"
                        name="stripe_subscription_monthly_price_id"
                        value="{{ old('stripe_subscription_monthly_price_id', $settings->stripe_subscription_monthly_price_id) }}"
                        class="vc-input"
                        placeholder="price_..." />
                    @error('stripe_subscription_monthly_price_id')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="stripe_subscription_yearly_price_id" class="vc-label">Yearly price ID</label>
                    <input
                        id="stripe_subscription_yearly_price_id"
                        name="stripe_subscription_yearly_price_id"
                        value="{{ old('stripe_subscription_yearly_price_id', $settings->stripe_subscription_yearly_price_id) }}"
                        class="vc-input"
                        placeholder="price_..." />
                    @error('stripe_subscription_yearly_price_id')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="subscription_currency" class="vc-label">Subscription currency</label>
                <select id="subscription_currency" name="subscription_currency" class="vc-input">
                    <option value="usd" @selected(old('subscription_currency', $settings->subscription_currency) === 'usd')>
                        USD
                    </option>
                    <option value="gbp" @selected(old('subscription_currency', $settings->subscription_currency) === 'gbp')>
                        GBP
                    </option>
                </select>
                @error('subscription_currency')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="stripe_billing_portal_configuration_id" class="vc-label">Portal configuration ID</label>
                <input
                    id="stripe_billing_portal_configuration_id"
                    name="stripe_billing_portal_configuration_id"
                    value="{{ old('stripe_billing_portal_configuration_id', $settings->stripe_billing_portal_configuration_id) }}"
                    class="vc-input"
                    placeholder="bpc_..." />
                <p class="vc-help">Optional. Leave empty to use Stripe default configuration.</p>
                @error('stripe_billing_portal_configuration_id')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input
                    class="vc-checkbox"
                    type="checkbox"
                    name="stripe_billing_portal_enabled"
                    value="1"
                    @checked(old('stripe_billing_portal_enabled', $settings->stripe_billing_portal_enabled)) />
                Enable Stripe Billing Portal
            </label>

            <div class="flex items-center gap-3">
                <button class="vc-btn-primary" type="submit">Save billing settings</button>
            </div>
        </form>
    </section>
</x-admin-layout>
