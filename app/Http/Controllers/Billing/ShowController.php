<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\PreorderReservation;
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

        $failedPreorders = (bool) config('learning.preorders_enabled')
            ? PreorderReservation::query()
                ->with('course')
                ->where(function ($query) use ($request): void {
                    $query
                        ->where('user_id', $request->user()->id)
                        ->orWhere('email', $request->user()->email);
                })
                ->whereIn('status', ['failed', 'action_required'])
                ->latest('updated_at')
                ->limit(10)
                ->get()
            : collect();

        return view('billing.show', [
            'subscription' => $activeSubscription,
            'settings' => $billingSettingsService->current(),
            'failedPreorders' => $failedPreorders,
        ]);
    }
}
