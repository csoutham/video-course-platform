<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_access_admin_orders(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.orders.index'))
            ->assertForbidden();
    }

    public function test_admin_users_can_view_orders_list(): void
    {
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
    }
}
