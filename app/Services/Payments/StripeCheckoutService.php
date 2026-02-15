<?php

namespace App\Services\Payments;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeCheckoutService
{
    public function createCheckoutSession(
        Course $course,
        ?User $user,
        string $customerEmail,
        ?string $promotionCode = null,
        bool $isGift = false,
        ?string $recipientEmail = null,
        ?string $recipientName = null,
        bool $giftMessagePresent = false,
    ): array {
        throw_unless($course->stripe_price_id, InvalidArgumentException::class, 'Course is missing stripe_price_id.');

        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $params = [
            'mode' => 'payment',
            'line_items' => [[
                'price' => $course->stripe_price_id,
                'quantity' => 1,
            ]],
            'customer_email' => $customerEmail,
            'success_url' => URL::route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => URL::route('checkout.cancel'),
            'metadata' => [
                'course_id' => (string) $course->id,
                'customer_email' => $customerEmail,
                'source' => 'videocourses-web',
                'user_id' => $user?->id ? (string) $user->id : null,
                'is_gift' => $isGift ? '1' : '0',
            ],
        ];

        if ($isGift) {
            throw_unless($recipientEmail, InvalidArgumentException::class, 'Recipient email is required for gift checkout.');

            $params['metadata']['recipient_email'] = $recipientEmail;
            $params['metadata']['recipient_name'] = $recipientName ?: null;
            $params['metadata']['gift_message_present'] = $giftMessagePresent ? '1' : '0';
        }

        if ($promotionCode) {
            $promotionCodeId = $this->resolvePromotionCodeId($stripe, $promotionCode);
            $params['discounts'] = [[
                'promotion_code' => $promotionCodeId,
            ]];
        } else {
            $params['allow_promotion_codes'] = true;
        }

        try {
            $session = $stripe->checkout->sessions->create($params);
        } catch (ApiErrorException) {
            throw new InvalidArgumentException('Unable to start checkout with that promotion code.');
        }

        return [
            'url' => (string) $session->url,
            'session_id' => (string) $session->id,
        ];
    }

    private function resolvePromotionCodeId(StripeClient $stripe, string $promotionCode): string
    {
        $promotionCode = trim($promotionCode);

        throw_if($promotionCode === '', InvalidArgumentException::class, 'Promotion code cannot be empty.');

        if (str_starts_with($promotionCode, 'promo_')) {
            return $promotionCode;
        }

        try {
            $matches = $stripe->promotionCodes->all([
                'code' => $promotionCode,
                'active' => true,
                'limit' => 1,
            ]);
        } catch (ApiErrorException) {
            throw new InvalidArgumentException('Unable to validate the promotion code right now.');
        }

        $match = $matches->data[0] ?? null;
        $matchId = is_object($match) ? ($match->id ?? null) : null;

        throw_if(! is_string($matchId) || $matchId === '', InvalidArgumentException::class, 'Promotion code is invalid or inactive.');

        return $matchId;
    }
}
