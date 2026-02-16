<?php

use App\Models\Course;
use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\PurchaseClaimToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest can create account and claim gift', function (): void {
    $course = Course::factory()->published()->create();

    $order = Order::create([
        'email' => 'buyer@example.com',
        'stripe_checkout_session_id' => 'cs_gift_claim_1',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    $gift = GiftPurchase::create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'buyer_email' => 'buyer@example.com',
        'recipient_email' => 'recipient@example.com',
        'status' => 'delivered',
        'delivered_at' => now(),
    ]);

    $claimToken = PurchaseClaimToken::create([
        'order_id' => $order->id,
        'gift_purchase_id' => $gift->id,
        'purpose' => 'gift_claim',
        'token' => 'gift_claim_token_1',
        'expires_at' => now()->addDay(),
    ]);

    $this->post(route('gift-claim.store', $claimToken->token), [
        'name' => 'Gift Recipient',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('my-courses.index'));

    $user = User::query()->firstWhere('email', 'recipient@example.com');
    $this->assertNotNull($user);

    $this->assertDatabaseHas('gift_purchases', [
        'id' => $gift->id,
        'status' => 'claimed',
        'claimed_by_user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('entitlements', [
        'order_id' => $order->id,
        'course_id' => $course->id,
        'user_id' => $user->id,
        'status' => 'active',
    ]);

});

test('logged in user must match recipient email', function (): void {
    $course = Course::factory()->published()->create();
    $user = User::factory()->create(['email' => 'wrong@example.com']);

    $order = Order::create([
        'email' => 'buyer@example.com',
        'stripe_checkout_session_id' => 'cs_gift_claim_2',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    $gift = GiftPurchase::create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'buyer_email' => 'buyer@example.com',
        'recipient_email' => 'recipient@example.com',
        'status' => 'delivered',
        'delivered_at' => now(),
    ]);

    $claimToken = PurchaseClaimToken::create([
        'order_id' => $order->id,
        'gift_purchase_id' => $gift->id,
        'purpose' => 'gift_claim',
        'token' => 'gift_claim_token_2',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->post(route('gift-claim.store', $claimToken->token))
        ->assertSessionHasErrors('claim');

});
