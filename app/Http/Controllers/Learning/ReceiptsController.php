<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\StripeReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ReceiptsController extends Controller
{
    public function index(Request $request, StripeReceiptService $receiptService): View
    {
        $orders = Order::query()
            ->with('items.course')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['paid', 'refunded'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        foreach ($orders as $order) {
            $receiptService->ensureReceiptUrl($order);
        }

        return view('learning.receipts', [
            'orders' => $orders,
        ]);
    }

    public function view(Request $request, Order $order, StripeReceiptService $receiptService): RedirectResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $receiptUrl = $receiptService->ensureReceiptUrl($order);

        abort_if(! $receiptUrl, 404, 'Stripe receipt is not available for this order yet.');

        return redirect()->away($receiptUrl);
    }
}
