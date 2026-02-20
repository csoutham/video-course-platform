<x-public-layout>
    <x-slot:title>Billing</x-slot>

    <div class="space-y-6">
        <div class="vc-heading-block">
            <p class="vc-eyebrow">Billing</p>
            <h1 class="vc-title">Subscription</h1>
            <p class="vc-subtitle">Manage your all-access subscription and payment details.</p>
        </div>

        @error('billing')
            <div class="vc-alert vc-alert-error">{{ $message }}</div>
        @enderror

        <section class="vc-panel p-6">
            @if ($subscription)
                <div class="space-y-2 text-sm text-slate-700">
                    <p>
                        <span class="font-semibold text-slate-900">Plan:</span>
                        {{ ucfirst($subscription->interval) }}
                    </p>
                    <p>
                        <span class="font-semibold text-slate-900">Status:</span>
                        {{ strtoupper($subscription->status) }}
                    </p>
                    @if ($subscription->current_period_end)
                        <p>
                            <span class="font-semibold text-slate-900">Current period ends:</span>
                            {{ $subscription->current_period_end->toFormattedDateString() }}
                        </p>
                    @endif
                </div>

                @if ($settings->stripe_billing_portal_enabled)
                    <form method="POST" action="{{ route('billing.portal') }}" class="mt-5">
                        @csrf
                        <button type="submit" class="vc-btn-primary">Manage in Stripe</button>
                    </form>
                @else
                    <p class="mt-4 text-sm text-slate-600">Billing management is not enabled yet.</p>
                @endif
            @else
                <p class="text-sm text-slate-600">No active subscription found for this account.</p>
                <a href="{{ route('courses.index') }}" class="vc-btn-secondary mt-4">Browse courses</a>
            @endif
        </section>

        @if (($failedPreorders ?? collect())->isNotEmpty())
            <section class="vc-panel p-6">
                <h2 class="vc-card-title">Preorder payment issues</h2>
                <p class="mt-1 text-sm text-slate-600">
                    One or more preorder charges failed at release. Update payment details and complete checkout from the course page.
                </p>
                <div class="mt-4 space-y-3">
                    @foreach ($failedPreorders as $reservation)
                        <article class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $reservation->course?->title ?? 'Course' }}</p>
                            <p class="mt-1 text-xs text-amber-800">
                                {{ strtoupper($reservation->status) }}{{ $reservation->failure_message ? ': '.$reservation->failure_message : '' }}
                            </p>
                            @if ($reservation->course?->slug)
                                <a href="{{ route('courses.show', $reservation->course->slug) }}" class="vc-btn-secondary mt-3">
                                    Open course page
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-public-layout>
