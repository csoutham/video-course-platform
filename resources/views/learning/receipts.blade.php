<x-public-layout>
    <x-slot:title>Receipts</x-slot:title>

    <div class="space-y-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Billing</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Receipts</h1>
            <p class="mt-2 text-sm text-slate-600">Download receipts for your purchased courses.</p>
        </div>

        @if ($orders->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600">
                No receipts available yet.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($orders as $order)
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Order #{{ $order->id }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ optional($order->paid_at)->format('M d, Y H:i') ?? 'Pending date' }} · {{ strtoupper($order->currency) }} {{ number_format($order->total_amount / 100, 2) }}
                                </p>
                            </div>
                            <a
                                href="{{ route('receipts.download', $order) }}"
                                class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            >
                                Download receipt
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-public-layout>
