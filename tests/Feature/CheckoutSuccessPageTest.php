<?php

use App\Models\Course;
use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PurchaseClaimToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest order with active claim token shows claim link', function (): void {
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
        ->assertSee('Add purchase to my account')
        ->assertSee(route('claim-purchase.show', $claimToken->token), false);

});

test('linked order shows library guidance', function (): void {
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

});

test('unknown session id shows processing message', function (): void {
    $this->get(route('checkout.success', ['session_id' => 'cs_missing_1']))
        ->assertOk()
        ->assertSee('finalizing your purchase', false)
        ->assertSee('Refresh status');

});

test('gift order shows gift sent guidance', function (): void {
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
        ->assertSee('Your gift is confirmed.', false);

});
