<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyGiftsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_their_gifts(): void
    {
        $buyer = User::factory()->create(['email' => 'buyer@example.com']);
        $course = Course::factory()->published()->create(['title' => 'Gifted Course']);

        $order = Order::create([
            'user_id' => $buyer->id,
            'email' => $buyer->email,
            'stripe_checkout_session_id' => 'cs_gift_list_1',
            'status' => 'paid',
            'subtotal_amount' => 9900,
            'discount_amount' => 0,
            'total_amount' => 9900,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        GiftPurchase::create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'buyer_user_id' => $buyer->id,
            'buyer_email' => $buyer->email,
            'recipient_email' => 'recipient@example.com',
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->get(route('gifts.index'))
            ->assertOk()
            ->assertSee('My Gifts')
            ->assertSee('Gifted Course')
            ->assertSee('recipient@example.com');
    }
}
