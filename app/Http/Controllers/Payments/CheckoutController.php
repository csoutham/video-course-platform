<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Audit\AuditLogService;
use App\Services\Payments\StripeCheckoutService;
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
        ]);

        $customerEmail = $request->user()?->email ?? ($validated['email'] ?? null);

        if (! $customerEmail) {
            return back()->withErrors([
                'email' => 'Email is required for checkout.',
            ]);
        }

        try {
            $checkoutUrl = $checkoutService->createCheckoutUrl(
                course: $course,
                user: $request->user(),
                customerEmail: $customerEmail,
                promotionCode: $validated['promotion_code'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'promotion_code' => $exception->getMessage(),
            ])->withInput();
        }

        $auditLogService->record(
            eventType: 'checkout_started',
            userId: $request->user()?->id,
            context: [
                'course_id' => $course->id,
                'email' => $customerEmail,
                'promotion_code' => $validated['promotion_code'] ?? null,
            ]
        );

        return redirect()->away($checkoutUrl);
    }
}
