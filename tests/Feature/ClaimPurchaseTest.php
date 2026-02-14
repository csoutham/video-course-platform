<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PurchaseClaimToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_account_and_claim_purchase(): void
    {
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
        ])->assertRedirect(route('dashboard'));

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
    }

    public function test_logged_in_user_with_matching_email_can_claim_purchase(): void
    {
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
            ->assertRedirect(route('dashboard'));

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
    }

    public function test_claim_fails_when_logged_in_user_email_does_not_match_order_email(): void
    {
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
    }
}
