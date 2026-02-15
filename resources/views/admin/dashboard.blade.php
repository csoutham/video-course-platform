<x-public-layout title="Admin Dashboard">
    <section class="vc-panel p-6">
        <div class="vc-heading-block">
            <p class="vc-eyebrow">Admin</p>
            <h1 class="vc-title">Dashboard</h1>
            <p class="vc-subtitle">High-level operational metrics for courses, customers, orders, and gift delivery.</p>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="vc-panel p-4">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Courses</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $metrics['courses_total'] }}</p>
            <p class="mt-1 text-sm text-slate-600">Published: {{ $metrics['courses_published'] }}</p>
        </article>
        <article class="vc-panel p-4">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Users</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $metrics['users_total'] }}</p>
            <p class="mt-1 text-sm text-slate-600">Registered accounts</p>
        </article>
        <article class="vc-panel p-4">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Orders</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $metrics['orders_total'] }}</p>
            <p class="mt-1 text-sm text-slate-600">Paid: {{ $metrics['orders_paid'] }}</p>
        </article>
        <article class="vc-panel p-4">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Revenue</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">
                ${{ number_format($metrics['revenue_cents'] / 100, 2) }}
            </p>
            <p class="mt-1 text-sm text-slate-600">Delivered gifts: {{ $metrics['gifts_delivered'] }}</p>
        </article>
    </section>

    <section class="vc-panel mt-6 p-6">
        <div class="mb-4 flex items-center justify-between gap-2">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Recent Orders</h2>
            <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Manage Courses</a>
        </div>

        @if ($recentOrders->isEmpty())
            <p class="mt-3 text-sm text-slate-600">No orders recorded yet.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">#{{ $order->id }}</td>
                                <td class="px-4 py-3">{{ $order->email }}</td>
                                <td class="px-4 py-3 uppercase">{{ $order->status }}</td>
                                <td class="px-4 py-3">
                                    {{ strtoupper($order->currency) }}
                                    {{ number_format($order->total_amount / 100, 2) }}
                                </td>
                                <td class="px-4 py-3">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-public-layout>
