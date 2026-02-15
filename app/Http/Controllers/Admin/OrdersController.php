<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

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
        ]);
    }
}
