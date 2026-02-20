<?php

namespace App\Services\Preorders;

use App\Models\Course;
use App\Models\User;
use App\Services\Billing\StripeCustomerResolver;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Stripe\StripeClient;

class PreorderCheckoutService
{
    public function __construct(
        private readonly StripeCustomerResolver $customerResolver,
    ) {
    }

    /**
     * @return array{url:string,session_id:string}
     */
    public function createCheckoutSession(Course $course, ?User $user, string $customerEmail): array
    {
        throw_unless((bool) config('learning.preorders_enabled'), InvalidArgumentException::class, 'Preorders are not enabled yet.');
        throw_unless($course->is_preorder_enabled, InvalidArgumentException::class, 'This course is not available for preorder.');
        throw_if($course->isReleased(), InvalidArgumentException::class, 'This course has already been released.');
        throw_unless($course->isPreorderWindowActive(), InvalidArgumentException::class, 'Preorder window is not active right now.');
        throw_unless((int) ($course->preorder_price_amount ?? 0) >= 100, InvalidArgumentException::class, 'Preorder price is not configured.');
        throw_unless($course->release_at, InvalidArgumentException::class, 'Release date is not configured.');

        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $params = [
            'mode' => 'setup',
            'success_url' => URL::route('courses.show', $course->slug).'?preorder=reserved',
            'cancel_url' => URL::route('courses.show', $course->slug).'?preorder=cancel',
            'metadata' => [
                'flow' => 'preorder_setup',
                'course_id' => (string) $course->id,
                'release_at' => $course->release_at?->toIso8601String(),
                'customer_email' => $customerEmail,
                'user_id' => $user?->id ? (string) $user->id : null,
                'source' => 'videocourses-web',
            ],
        ];

        if ($user) {
            $params['customer'] = $this->customerResolver->resolveOrCreateForUser($user);
        } else {
            $params['customer_email'] = $customerEmail;
        }

        $session = $stripe->checkout->sessions->create($params);

        return [
            'url' => (string) $session->url,
            'session_id' => (string) $session->id,
        ];
    }

    public function resolveSetupIntentPaymentMethod(string $setupIntentId): ?string
    {
        if ($setupIntentId === '') {
            return null;
        }

        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $setupIntent = $stripe->setupIntents->retrieve($setupIntentId, []);

        $paymentMethod = $setupIntent->payment_method;

        if (is_string($paymentMethod) && $paymentMethod !== '') {
            return $paymentMethod;
        }

        if (is_object($paymentMethod) && isset($paymentMethod->id) && is_string($paymentMethod->id) && $paymentMethod->id !== '') {
            return $paymentMethod->id;
        }

        return null;
    }
}
