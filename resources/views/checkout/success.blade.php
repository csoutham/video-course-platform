<x-public-layout>
    <x-slot:title>Checkout Success</x-slot:title>

    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6">
        <h1 class="text-xl font-semibold text-emerald-900">Payment received</h1>

        @if ($isGiftOrder)
            <p class="mt-2 text-sm text-emerald-800">
                Your gift purchase is confirmed. We sent the gift claim email to the recipient and a confirmation to your email.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                @auth
                    <a
                        href="{{ route('gifts.index') }}"
                        class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600"
                    >
                        View my gifts
                    </a>
                @endauth
                <a href="{{ route('courses.index') }}" class="inline-flex items-center text-sm font-semibold text-emerald-900 hover:text-emerald-700">
                    Back to courses
                </a>
            </div>
        @elseif ($claimUrl)
            <p class="mt-2 text-sm text-emerald-800">
                Your payment has been confirmed. Continue with the secure claim link below to activate access for this purchase.
            </p>

            <a
                href="{{ $claimUrl }}"
                class="mt-4 inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600"
            >
                Claim your purchase
            </a>
        @elseif ($order?->user_id)
            <p class="mt-2 text-sm text-emerald-800">
                This purchase is linked to an account. Sign in to continue to your course library.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                <a
                    href="{{ route('my-courses.index') }}"
                    class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600"
                >
                    Go to my courses
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center text-sm font-semibold text-emerald-900 hover:text-emerald-700">
                    Sign in
                </a>
            </div>
        @elseif ($sessionId)
            <p class="mt-2 text-sm text-emerald-800">
                We are finalizing your purchase. If your claim link is not visible yet, refresh this page in a few seconds.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                <a
                    href="{{ route('checkout.success', ['session_id' => $sessionId]) }}"
                    class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600"
                >
                    Refresh status
                </a>
                <a href="{{ route('courses.index') }}" class="inline-flex items-center text-sm font-semibold text-emerald-900 hover:text-emerald-700">
                    Back to courses
                </a>
            </div>
        @else
            <p class="mt-2 text-sm text-emerald-800">
                Your order is being finalized. Access will appear in your account after webhook processing.
            </p>
            <a href="{{ route('courses.index') }}" class="mt-4 inline-block text-sm font-semibold text-emerald-900 hover:text-emerald-700">
                Back to courses
            </a>
        @endif
    </div>
</x-public-layout>
