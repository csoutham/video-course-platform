<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Audit\AuditLogService;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        Course $course,
        StripeCheckoutService $checkoutService,
        AuditLogService $auditLogService,
    ): RedirectResponse {
        abort_if(! $course->is_published, 404);
        abort_if(! $course->stripe_price_id, 422, 'Course is not purchasable yet.');

        $validated = $request->validate([
            'email' => ['nullable', 'email'],
            'promotion_code' => ['nullable', 'string', 'max:255'],
            'is_gift' => ['nullable', 'boolean'],
            'recipient_email' => ['nullable', 'email', 'required_if:is_gift,1'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'gift_message' => ['nullable', 'string', 'max:500'],
        ]);

        $customerEmail = $request->user()?->email ?? ($validated['email'] ?? null);
        $isGift = (bool) ($validated['is_gift'] ?? false);

        if (! $customerEmail) {
            return back()->withErrors([
                'email' => 'Email is required for checkout.',
            ]);
        }

        if ($isGift && ! config('learning.gifts_enabled')) {
            return back()->withErrors([
                'recipient_email' => 'Gift purchases are not enabled yet.',
            ])->withInput();
        }

        if ($isGift) {
            $rateLimitKey = 'gift-checkout:'.$request->ip();

            if (RateLimiter::tooManyAttempts($rateLimitKey, 12)) {
                return back()->withErrors([
                    'recipient_email' => 'Too many gift checkout attempts. Please try again shortly.',
                ])->withInput();
            }

            RateLimiter::hit($rateLimitKey, 60);
        }

        try {
            $session = $checkoutService->createCheckoutSession(
                course: $course,
                user: $request->user(),
                customerEmail: $customerEmail,
                promotionCode: $validated['promotion_code'] ?? null,
                isGift: $isGift,
                recipientEmail: $validated['recipient_email'] ?? null,
                recipientName: $validated['recipient_name'] ?? null,
                giftMessagePresent: filled($validated['gift_message'] ?? null),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'promotion_code' => $exception->getMessage(),
            ])->withInput();
        }

        if ($isGift && ($validated['gift_message'] ?? null) !== null && ($session['session_id'] ?? null)) {
            Cache::put('gift-checkout:'.$session['session_id'], [
                'recipient_email' => $validated['recipient_email'] ?? null,
                'recipient_name' => $validated['recipient_name'] ?? null,
                'gift_message' => $validated['gift_message'],
            ], now()->addDays(2));
        }

        $auditLogService->record(
            eventType: 'checkout_started',
            userId: $request->user()?->id,
            context: [
                'course_id' => $course->id,
                'email' => $customerEmail,
                'promotion_code' => $validated['promotion_code'] ?? null,
                'is_gift' => $isGift,
                'recipient_email' => $validated['recipient_email'] ?? null,
            ]
        );

        return redirect()->away((string) ($session['url'] ?? ''));
    }
}
