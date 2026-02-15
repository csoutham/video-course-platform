<x-public-layout title="Admin Orders">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Orders</h1>
                <p class="vc-subtitle">Operational order log with payment and gift indicators.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="vc-btn-secondary">Back to Dashboard</a>
        </div>
    </section>

    <section class="vc-panel mt-6 p-6">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <label class="text-sm font-medium text-slate-700" for="status">Filter by status</label>
            <select id="status" name="status" class="vc-input mt-0 max-w-xs">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>
                        {{ strtoupper($status) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="vc-btn-primary">Apply</button>
            @if ($selectedStatus !== '')
                <a href="{{ route('admin.orders.index') }}" class="vc-btn-secondary">Clear</a>
            @endif
        </form>
    </section>

    <section class="vc-panel mt-6 overflow-hidden">
        @if ($orders->isEmpty())
            <p class="p-6 text-sm text-slate-600">No orders found.</p>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Gift</th>
                        <th class="px-4 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">#{{ $order->id }}</td>
                            <td class="px-4 py-3">{{ $order->email }}</td>
                            <td class="px-4 py-3 uppercase">{{ $order->status }}</td>
                            <td class="px-4 py-3">{{ $order->items_count }}</td>
                            <td class="px-4 py-3">
                                {{ strtoupper($order->currency) }}
                                {{ number_format($order->total_amount / 100, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $order->giftPurchase ? strtoupper($order->giftPurchase->status) : 'No' }}
                            </td>
                            <td class="px-4 py-3">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    @if ($orders->hasPages())
        <section class="mt-4">
            {{ $orders->links() }}
        </section>
    @endif
</x-public-layout>
