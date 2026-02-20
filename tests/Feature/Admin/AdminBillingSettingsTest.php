<?php

use App\Models\BillingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non admin users cannot access admin billing settings', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.billing.edit'))
        ->assertForbidden();
});

test('admin users can view admin billing settings', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.billing.edit'))
        ->assertOk()
        ->assertSeeText('Billing Settings');
});

test('admin users can update admin billing settings', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.billing.update'), [
            'stripe_subscription_monthly_price_id' => 'price_monthly_1',
            'stripe_subscription_yearly_price_id' => 'price_yearly_1',
            'subscription_currency' => 'gbp',
            'stripe_billing_portal_enabled' => '1',
            'stripe_billing_portal_configuration_id' => 'bpc_123',
        ])->assertRedirect(route('admin.billing.edit'));

    $this->assertDatabaseHas('billing_settings', [
        'id' => 1,
        'stripe_subscription_monthly_price_id' => 'price_monthly_1',
        'stripe_subscription_yearly_price_id' => 'price_yearly_1',
        'subscription_currency' => 'gbp',
        'stripe_billing_portal_enabled' => 1,
        'stripe_billing_portal_configuration_id' => 'bpc_123',
    ]);

    expect(BillingSetting::query()->first())->not()->toBeNull();
});
