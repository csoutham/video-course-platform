<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    private const FILTERABLE_STATUSES = [
        'paid',
        'partially_refunded',
        'refunded',
        'failed',
    ];

    public function index(Request $request): View
    {
        $requestedStatus = $request->string('status')->toString();
        $status = in_array($requestedStatus, self::FILTERABLE_STATUSES, true) ? $requestedStatus : '';

        $orders = Order::query()
            ->with(['giftPurchase'])
            ->withCount('items')
            ->when(
                $status !== '',
                fn ($query) => $query->where('status', $status),
            )
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $statuses = Order::query()
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => $statuses,
            'selectedStatus' => $status,
            'quickStatuses' => self::FILTERABLE_STATUSES,
        ]);
    }
}
