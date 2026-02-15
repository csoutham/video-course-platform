<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CheckoutSuccessController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sessionId = $request->query('session_id');

        $order = null;
        $claimUrl = null;

        if (is_string($sessionId) && $sessionId !== '') {
            $order = Order::query()
                ->with('purchaseClaimToken')
                ->firstWhere('stripe_checkout_session_id', $sessionId);

            if ($order?->purchaseClaimToken
                && ! $order->purchaseClaimToken->consumed_at
                && $order->purchaseClaimToken->expires_at
                && $order->purchaseClaimToken->expires_at->isFuture()) {
                $claimUrl = route('claim-purchase.show', $order->purchaseClaimToken->token);
            }
        }

        return view('checkout.success', [
            'order' => $order,
            'claimUrl' => $claimUrl,
            'sessionId' => $sessionId,
        ]);
    }
}
