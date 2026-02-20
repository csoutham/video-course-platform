<?php

namespace App\Services\Billing;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SubscriptionCheckoutService
{
    public function __construct(
        private readonly BillingSettingsService $billingSettingsService,
        private readonly StripeCustomerResolver $customerResolver,
    ) {
    }

    /**
     * @return array{url:string,session_id:string}
     */
    public function createCheckoutSession(User $user, string $interval, ?string $promotionCode = null): array
    {
        $priceId = $this->billingSettingsService->stripePriceIdForInterval($interval);
        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $customerId = $this->customerResolver->resolveOrCreateForUser($user);

        $params = [
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => URL::route('billing.show').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => URL::route('billing.show'),
            'metadata' => [
                'flow' => 'subscription',
                'interval' => $interval,
                'user_id' => (string) $user->id,
                'customer_email' => $user->email,
                'source' => 'videocourses-web',
            ],
            'subscription_data' => [
                'metadata' => [
                    'flow' => 'subscription',
                    'interval' => $interval,
                    'user_id' => (string) $user->id,
                    'customer_email' => $user->email,
                    'source' => 'videocourses-web',
                ],
            ],
        ];

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
            throw new InvalidArgumentException('Unable to start subscription checkout right now.');
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
