<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\GiftPurchase;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'courses_total' => Course::query()->count(),
            'courses_published' => Course::query()->where('is_published', true)->count(),
            'users_total' => User::query()->count(),
            'orders_total' => Order::query()->count(),
            'orders_paid' => Order::query()->where('status', 'paid')->count(),
            'revenue_cents' => Order::query()
                ->where('status', 'paid')
                ->sum('total_amount'),
            'gifts_delivered' => GiftPurchase::query()->where('status', 'delivered')->count(),
        ];

        $recentOrders = Order::query()
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'recentOrders' => $recentOrders,
        ]);
    }
}
