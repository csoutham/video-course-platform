<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view their receipts', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create(['title' => 'Receipt Course']);

    $order = Order::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_receipt_1',
        'status' => 'paid',
        'subtotal_amount' => 1500,
        'discount_amount' => 0,
        'total_amount' => 1500,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'unit_amount' => 1500,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('receipts.index'))
        ->assertOk()
        ->assertSee('Receipts')
        ->assertSee('Order '.$order->public_id)
        ->assertSee(route('receipts.view', $order), false);

});

test('orders use obfuscated public ids in receipt links', function (): void {
    $user = User::factory()->create();

    $order = Order::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_receipt_public_id_1',
        'status' => 'paid',
        'subtotal_amount' => 1000,
        'discount_amount' => 0,
        'total_amount' => 1000,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    expect($order->public_id)->toStartWith('ord_');
    expect($order->public_id)->toMatch('/^ord_[a-z0-9]{26}$/');

    $url = route('receipts.view', $order, false);

    expect($url)->toContain('/receipts/'.$order->public_id);
    expect($url)->not->toContain('/'.$order->id);
});

test('user can redirect to their stripe receipt', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create(['title' => 'Downloadable Receipt Course']);

    $order = Order::query()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_receipt_2',
        'stripe_receipt_url' => 'https://pay.stripe.com/receipts/test_receipt_2',
        'status' => 'paid',
        'subtotal_amount' => 2500,
        'discount_amount' => 0,
        'total_amount' => 2500,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'unit_amount' => 2500,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)
        ->get(route('receipts.view', $order));

    $response->assertRedirect('https://pay.stripe.com/receipts/test_receipt_2');

});

test('user cannot view another users receipt', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $order = Order::query()->create([
        'user_id' => $owner->id,
        'email' => $owner->email,
        'stripe_checkout_session_id' => 'cs_receipt_3',
        'status' => 'paid',
        'subtotal_amount' => 2500,
        'discount_amount' => 0,
        'total_amount' => 2500,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    $this->actingAs($otherUser)
        ->get(route('receipts.view', $order))
        ->assertForbidden();

});
