<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PurchaseClaimToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutSuccessPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_order_with_active_claim_token_shows_claim_link(): void
    {
        $course = Course::factory()->published()->create();

        $order = Order::query()->create([
            'email' => 'guestbuyer@example.com',
            'stripe_checkout_session_id' => 'cs_success_guest_1',
            'status' => 'paid',
            'subtotal_amount' => 9900,
            'discount_amount' => 0,
            'total_amount' => 9900,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'unit_amount' => 9900,
            'quantity' => 1,
        ]);

        $claimToken = PurchaseClaimToken::query()->create([
            'order_id' => $order->id,
            'token' => 'success_claim_token_1',
            'expires_at' => now()->addDay(),
        ]);

        $this->get(route('checkout.success', ['session_id' => 'cs_success_guest_1']))
            ->assertOk()
            ->assertSee('Claim your purchase')
            ->assertSee(route('claim-purchase.show', $claimToken->token), false);
    }

    public function test_linked_order_shows_library_guidance(): void
    {
        $user = User::factory()->create();

        Order::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'stripe_checkout_session_id' => 'cs_success_user_1',
            'status' => 'paid',
            'subtotal_amount' => 9900,
            'discount_amount' => 0,
            'total_amount' => 9900,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        $this->get(route('checkout.success', ['session_id' => 'cs_success_user_1']))
            ->assertOk()
            ->assertSee('Go to my courses')
            ->assertSee(route('my-courses.index'), false);
    }

    public function test_unknown_session_id_shows_processing_message(): void
    {
        $this->get(route('checkout.success', ['session_id' => 'cs_missing_1']))
            ->assertOk()
            ->assertSee('finalizing your purchase', false)
            ->assertSee('Refresh status');
    }

    public function test_gift_order_shows_gift_sent_guidance(): void
    {
        $course = Course::factory()->published()->create();

        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'stripe_checkout_session_id' => 'cs_success_gift_1',
            'status' => 'paid',
            'subtotal_amount' => 9900,
            'discount_amount' => 0,
            'total_amount' => 9900,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        GiftPurchase::query()->create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'buyer_email' => 'buyer@example.com',
            'recipient_email' => 'recipient@example.com',
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->get(route('checkout.success', ['session_id' => 'cs_success_gift_1']))
            ->assertOk()
            ->assertSee('gift purchase is confirmed', false);
    }
}
