<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Billing\SubscriptionCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SubscriptionCheckoutController extends Controller
{
    public function __invoke(Request $request, SubscriptionCheckoutService $checkoutService): RedirectResponse
    {
        abort_unless((bool) config('learning.subscriptions_enabled'), 404);

        $validated = $request->validate([
            'interval' => ['required', 'string', 'in:monthly,yearly'],
            'promotion_code' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $session = $checkoutService->createCheckoutSession(
                user: $request->user(),
                interval: (string) $validated['interval'],
                promotionCode: $validated['promotion_code'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'subscription' => $exception->getMessage(),
            ])->withInput();
        }

        return redirect()->away((string) ($session['url'] ?? ''));
    }
}
