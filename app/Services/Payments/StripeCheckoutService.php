<?php

namespace App\Services\Payments;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Stripe\StripeClient;

class StripeCheckoutService
{
    public function createCheckoutUrl(Course $course, ?User $user, string $customerEmail, ?string $promotionCode = null): string
    {
        if (! $course->stripe_price_id) {
            throw new InvalidArgumentException('Course is missing stripe_price_id.');
        }

        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $params = [
            'mode' => 'payment',
            'line_items' => [[
                'price' => $course->stripe_price_id,
                'quantity' => 1,
            ]],
            'customer_email' => $customerEmail,
            'allow_promotion_codes' => true,
            'success_url' => URL::route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => URL::route('checkout.cancel'),
            'metadata' => [
                'course_id' => (string) $course->id,
                'customer_email' => $customerEmail,
                'source' => 'videocourses-web',
                'user_id' => $user?->id ? (string) $user->id : null,
            ],
        ];

        if ($promotionCode) {
            $params['discounts'] = [[
                'promotion_code' => $promotionCode,
            ]];
        }

        $session = $stripe->checkout->sessions->create($params);

        return (string) $session->url;
    }
}
