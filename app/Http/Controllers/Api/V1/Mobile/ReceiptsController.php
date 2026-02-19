<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\StripeReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptsController extends Controller
{
    public function __invoke(Request $request, StripeReceiptService $receiptService): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['paid', 'refunded'])
            ->latest('paid_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Order $order): bool => $order->isStripeReceiptEligible())
            ->values();

        $receipts = $orders
            ->map(function (Order $order) use ($receiptService): ?array {
                $receiptUrl = $receiptService->ensureReceiptUrl($order);

                if (! $receiptUrl) {
                    return null;
                }

                return [
                    'order_public_id' => $order->public_id,
                    'status' => $order->status,
                    'total_amount' => (int) $order->total_amount,
                    'currency' => $order->currency,
                    'paid_at' => $order->paid_at?->toIso8601String(),
                    'refunded_at' => $order->refunded_at?->toIso8601String(),
                    'receipt_url' => $receiptUrl,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'receipts' => $receipts,
        ]);
    }
}
