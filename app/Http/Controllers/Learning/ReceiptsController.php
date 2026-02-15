<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptsController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('items.course')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['paid', 'refunded'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        return view('learning.receipts', [
            'orders' => $orders,
        ]);
    }

    public function download(Request $request, Order $order): StreamedResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->loadMissing('items.course');

        $content = $this->receiptContent($order);
        $filename = 'receipt-order-'.$order->id.'.txt';

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function receiptContent(Order $order): string
    {
        $lines = [
            'VideoCourses Purchase Receipt',
            'Order ID: '.$order->id,
            'Session ID: '.$order->stripe_checkout_session_id,
            'Email: '.$order->email,
            'Status: '.$order->status,
            'Paid At: '.optional($order->paid_at)->toDateTimeString(),
            'Currency: '.strtoupper($order->currency),
            'Subtotal: '.number_format($order->subtotal_amount / 100, 2),
            'Discount: '.number_format($order->discount_amount / 100, 2),
            'Total: '.number_format($order->total_amount / 100, 2),
            '',
            'Items:',
        ];

        foreach ($order->items as $item) {
            $courseTitle = $item->course?->title ?? 'Course #'.$item->course_id;
            $lines[] = '- '.$courseTitle.' x'.$item->quantity.' @ '.number_format($item->unit_amount / 100, 2).' '.strtoupper($order->currency);
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
