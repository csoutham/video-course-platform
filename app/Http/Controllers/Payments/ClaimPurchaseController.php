<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Claims\PurchaseClaimService;
use App\Services\Payments\EntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ClaimPurchaseController extends Controller
{
    public function show(string $token, PurchaseClaimService $claimService): View
    {
        $claimToken = $claimService->resolveActiveToken($token);

        abort_if(! $claimToken, 404);

        $order = $claimToken->order()->with('items.course')->firstOrFail();
        $existingUser = User::query()->firstWhere('email', $order->email);

        return view('checkout.claim', [
            'claimToken' => $claimToken,
            'order' => $order,
            'existingUser' => $existingUser,
            'authenticatedEmailMatches' => Auth::check() && Auth::user()->email === $order->email,
        ]);
    }

    public function store(
        Request $request,
        string $token,
        PurchaseClaimService $claimService,
        EntitlementService $entitlementService,
    ): RedirectResponse {
        $claimToken = $claimService->resolveActiveToken($token);

        abort_if(! $claimToken, 404);

        $order = $claimToken->order()->with('items')->firstOrFail();

        if ($order->status !== 'paid') {
            throw ValidationException::withMessages([
                'claim' => 'This order is not eligible for claim yet.',
            ]);
        }

        $user = $request->user();

        if ($user) {
            if ($user->email !== $order->email) {
                throw ValidationException::withMessages([
                    'claim' => 'Your logged in account email does not match the purchase email.',
                ]);
            }
        } else {
            $existingUser = User::query()->firstWhere('email', $order->email);

            if ($existingUser) {
                throw ValidationException::withMessages([
                    'claim' => 'Account exists for this email. Log in first and retry claim.',
                ]);
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'confirmed', 'min:8'],
            ]);

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $order->email,
                'password' => $validated['password'],
            ]);

            Auth::login($user);
        }

        $order->forceFill([
            'user_id' => $user->id,
        ])->save();

        $entitlementService->grantForOrder($order);

        $claimToken->forceFill([
            'consumed_at' => now(),
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Purchase claimed successfully.');
    }
}
