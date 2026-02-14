<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, Course $course, StripeCheckoutService $checkoutService): RedirectResponse
    {
        abort_if(! $course->is_published, 404);
        abort_if(! $course->stripe_price_id, 422, 'Course is not purchasable yet.');

        $validated = $request->validate([
            'email' => ['nullable', 'email'],
            'promotion_code' => ['nullable', 'string', 'max:255'],
        ]);

        $customerEmail = $request->user()?->email ?? ($validated['email'] ?? null);

        if (! $customerEmail) {
            return back()->withErrors([
                'email' => 'Email is required for checkout.',
            ]);
        }

        $checkoutUrl = $checkoutService->createCheckoutUrl(
            course: $course,
            user: $request->user(),
            customerEmail: $customerEmail,
            promotionCode: $validated['promotion_code'] ?? null,
        );

        return redirect()->away($checkoutUrl);
    }
}
