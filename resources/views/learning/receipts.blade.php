<x-public-layout>
    <x-slot:title>Receipts</x-slot>

    <div class="space-y-6">
        <div class="vc-heading-block">
            <p class="vc-eyebrow">Billing</p>
            <h1 class="vc-title">Receipts</h1>
            <p class="vc-subtitle">Download receipts for your purchased courses.</p>
        </div>

        @if ($orders->isEmpty())
            <div class="vc-panel border-dashed p-6 text-sm text-slate-600">No receipts available yet.</div>
        @else
            <div class="space-y-3">
                @foreach ($orders as $order)
                    <article class="vc-panel p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Order {{ $order->public_id }}</p>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ optional($order->paid_at)->format('M d, Y H:i') ?? 'Pending date' }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ strtoupper($order->currency) }}
                                    {{ number_format($order->total_amount / 100, 2) }}
                                </p>
                            </div>
                            <a href="{{ route('receipts.view', $order) }}" target="_blank" class="vc-btn-secondary">
                                View Stripe receipt
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-public-layout>
