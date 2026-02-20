<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PortalController extends Controller
{
    public function __invoke(Request $request, BillingPortalService $billingPortalService): RedirectResponse
    {
        abort_unless((bool) config('learning.subscriptions_enabled'), 404);

        try {
            $portalUrl = $billingPortalService->createPortalUrl($request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'billing' => $exception->getMessage(),
            ]);
        }

        return redirect()->away($portalUrl);
    }
}
