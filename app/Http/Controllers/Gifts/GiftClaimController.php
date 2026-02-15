<?php

namespace App\Http\Controllers\Gifts;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Claims\GiftClaimService;
use App\Services\Gifts\GiftRedemptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GiftClaimController extends Controller
{
    public function show(string $token, GiftClaimService $giftClaimService): View
    {
        $claimToken = $giftClaimService->resolveActiveGiftToken($token);

        abort_if(! $claimToken, 404);

        $giftPurchase = $claimToken->giftPurchase()->with('course')->firstOrFail();
        $existingUser = User::query()->firstWhere('email', $giftPurchase->recipient_email);

        return view('checkout.gift-claim', [
            'claimToken' => $claimToken,
            'giftPurchase' => $giftPurchase,
            'existingUser' => $existingUser,
            'authenticatedEmailMatches' => Auth::check() && Auth::user()->email === $giftPurchase->recipient_email,
        ]);
    }

    public function store(
        Request $request,
        string $token,
        GiftClaimService $giftClaimService,
        GiftRedemptionService $giftRedemptionService,
    ): RedirectResponse {
        $claimToken = $giftClaimService->resolveActiveGiftToken($token);

        abort_if(! $claimToken, 404);

        $giftPurchase = $claimToken->giftPurchase()->with('course')->firstOrFail();

        if ($giftPurchase->status === 'revoked') {
            throw ValidationException::withMessages([
                'claim' => 'This gift is no longer redeemable.',
            ]);
        }

        $user = $request->user();

        if ($user) {
            if ($user->email !== $giftPurchase->recipient_email) {
                throw ValidationException::withMessages([
                    'claim' => 'Log in with the recipient email to claim this gift.',
                ]);
            }
        } else {
            $existingUser = User::query()->firstWhere('email', $giftPurchase->recipient_email);

            if ($existingUser) {
                throw ValidationException::withMessages([
                    'claim' => 'Account exists for the recipient email. Log in first and retry claim.',
                ]);
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'confirmed', 'min:8'],
            ]);

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $giftPurchase->recipient_email,
                'password' => $validated['password'],
            ]);

            Auth::login($user);
        }

        $giftRedemptionService->redeem($claimToken, $user);

        return to_route('my-courses.index')->with('status', 'Gift claimed successfully.');
    }
}
