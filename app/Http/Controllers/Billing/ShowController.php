<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    public function __invoke(Request $request, BillingSettingsService $billingSettingsService): View
    {
        abort_unless((bool) config('learning.subscriptions_enabled'), 404);

        $activeSubscription = $request->user()
            ->subscriptions()
            ->orderByDesc('id')
            ->get()
            ->first(fn ($subscription) => $subscription->isAccessActive());

        return view('billing.show', [
            'subscription' => $activeSubscription,
            'settings' => $billingSettingsService->current(),
        ]);
    }
}
