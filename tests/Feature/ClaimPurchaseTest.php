<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PurchaseClaimToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest can create account and claim purchase', function (): void {
    $course = Course::factory()->published()->create();

    $order = Order::create([
        'email' => 'newbuyer@example.com',
        'stripe_checkout_session_id' => 'cs_claim_1',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'unit_amount' => 9900,
        'quantity' => 1,
    ]);

    $claimToken = PurchaseClaimToken::create([
        'order_id' => $order->id,
        'token' => 'claim_token_guest_1',
        'expires_at' => now()->addDay(),
    ]);

    $this->post(route('claim-purchase.store', $claimToken->token), [
        'name' => 'New Buyer',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('my-courses.index'));

    $user = User::query()->firstWhere('email', 'newbuyer@example.com');
    $this->assertNotNull($user);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('entitlements', [
        'user_id' => $user->id,
        'course_id' => $course->id,
        'order_id' => $order->id,
        'status' => 'active',
    ]);

    $this->assertNotNull($claimToken->fresh()->consumed_at);

});

test('logged in user with matching email can claim purchase', function (): void {
    $user = User::factory()->create([
        'email' => 'existingbuyer@example.com',
    ]);
    $course = Course::factory()->published()->create();

    $order = Order::create([
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_claim_2',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'unit_amount' => 9900,
        'quantity' => 1,
    ]);

    $claimToken = PurchaseClaimToken::create([
        'order_id' => $order->id,
        'token' => 'claim_token_existing_1',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->post(route('claim-purchase.store', $claimToken->token))
        ->assertRedirect(route('my-courses.index'));

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('entitlements', [
        'user_id' => $user->id,
        'course_id' => $course->id,
        'order_id' => $order->id,
        'status' => 'active',
    ]);

});

test('claim fails when logged in user email does not match order email', function (): void {
    $user = User::factory()->create([
        'email' => 'wrong@example.com',
    ]);
    $course = Course::factory()->published()->create();

    $order = Order::create([
        'email' => 'correct@example.com',
        'stripe_checkout_session_id' => 'cs_claim_3',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'unit_amount' => 9900,
        'quantity' => 1,
    ]);

    $claimToken = PurchaseClaimToken::create([
        'order_id' => $order->id,
        'token' => 'claim_token_mismatch_1',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->post(route('claim-purchase.store', $claimToken->token))
        ->assertSessionHasErrors('claim');

    $this->assertNull($claimToken->fresh()->consumed_at);

});
