<?php

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('order and entitlement relationships are persisted', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $order = Order::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'stripe_checkout_session_id' => 'cs_test_123',
        'status' => 'paid',
        'subtotal_amount' => 9900,
        'discount_amount' => 0,
        'total_amount' => 9900,
        'currency' => 'usd',
        'paid_at' => now(),
    ]);

    $item = OrderItem::create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'unit_amount' => 9900,
        'quantity' => 1,
    ]);

    $entitlement = Entitlement::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'order_id' => $order->id,
        'status' => 'active',
        'granted_at' => now(),
    ]);

    $this->assertTrue($item->order->is($order));
    $this->assertTrue($item->course->is($course));
    $this->assertTrue($entitlement->user->is($user));
    $this->assertTrue($entitlement->course->is($course));
    $this->assertTrue($entitlement->order->is($order));

});

test('stripe event payload is cast to array', function (): void {
    $event = StripeEvent::create([
        'stripe_event_id' => 'evt_test_123',
        'event_type' => 'checkout.session.completed',
        'payload_json' => [
            'id' => 'evt_test_123',
            'data' => ['object' => ['id' => 'cs_test_123']],
        ],
    ]);

    $this->assertIsArray($event->payload_json);
    $this->assertSame('evt_test_123', $event->payload_json['id']);

});
