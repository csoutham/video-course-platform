<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non admin users cannot access admin orders', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.orders.index'))
        ->assertForbidden();

});

test('admin users can view orders list', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Order::query()->create([
        'email' => 'buyer@example.com',
        'stripe_checkout_session_id' => 'cs_test_admin_orders_1',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
    ]);

    $this->get(route('admin.orders.index'))
        ->assertOk()
        ->assertSeeText('Orders')
        ->assertSeeText('buyer@example.com')
        ->assertSeeText('PAID');

});
