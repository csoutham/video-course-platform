<x-public-layout>
    <x-slot:title>Checkout Success</x-slot>

    <div class="vc-panel-soft border-emerald-200 bg-emerald-50 p-6">
        <h1 class="text-xl font-semibold text-emerald-900">
            {{ ($order?->total_amount ?? 1) === 0 ? 'Enrollment successful' : 'Payment successful' }}
        </h1>

        @if ($isGiftOrder)
            <p class="mt-2 text-sm text-emerald-800">
                Your gift is confirmed. We have emailed the claim link to the recipient, and sent you a confirmation
                email too.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                @auth
                    <a
                        href="{{ route('gifts.index') }}"
                        class="inline-flex items-center rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">
                        View my gifts
                    </a>
                @endauth

                <a
                    href="{{ route('courses.index') }}"
                    class="inline-flex items-center text-sm font-semibold text-emerald-900 hover:text-emerald-700">
                    Browse courses
                </a>
            </div>
        @elseif ($claimUrl)
            <p class="mt-2 text-sm text-emerald-800">
                Your payment is confirmed. Use the secure link below to add this purchase to your account.
            </p>

            <a
                href="{{ $claimUrl }}"
                class="mt-4 inline-flex items-center rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">
                Add purchase to my account
            </a>
        @elseif ($order?->user_id)
            <p class="mt-2 text-sm text-emerald-800">
                This purchase is already linked to an account. Sign in to continue to your course library.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                <a
                    href="{{ route('my-courses.index') }}"
                    class="inline-flex items-center rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">
                    Go to my courses
                </a>
                <a
                    href="{{ route('login') }}"
                    class="inline-flex items-center text-sm font-semibold text-emerald-900 hover:text-emerald-700">
                    Sign in now
                </a>
            </div>
        @elseif ($sessionId)
            <p class="mt-2 text-sm text-emerald-800">
                We are still finalizing your purchase. If the claim link is not visible yet, refresh this page in a few
                seconds.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                <a
                    href="{{ route('checkout.success', ['session_id' => $sessionId]) }}"
                    class="inline-flex items-center rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">
                    Refresh status
                </a>
                <a
                    href="{{ route('courses.index') }}"
                    class="inline-flex items-center text-sm font-semibold text-emerald-900 hover:text-emerald-700">
                    Browse courses
                </a>
            </div>
        @else
            <p class="mt-2 text-sm text-emerald-800">
                Your order is being finalized. Course access will appear in your account shortly.
            </p>
            <a
                href="{{ route('courses.index') }}"
                class="mt-4 inline-block text-sm font-semibold text-emerald-900 hover:text-emerald-700">
                Browse courses
            </a>
        @endif
    </div>
</x-public-layout>
