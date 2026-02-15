<x-public-layout>
    <x-slot:title>Claim Gift</x-slot:title>

    <div class="vc-panel mx-auto max-w-xl space-y-6 p-6">
        <div>
            <p class="vc-eyebrow">Gift claim</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Claim your gifted course</h1>
            <p class="mt-2 text-sm text-slate-600">
                Course: <span class="font-semibold text-slate-900">{{ $giftPurchase->course->title }}</span>
            </p>
            <p class="mt-1 text-sm text-slate-600">
                Gifted to: <span class="font-semibold text-slate-900">{{ $giftPurchase->recipient_email }}</span>
            </p>
        </div>

        @error('claim')
            <p class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        @auth
            @if ($authenticatedEmailMatches)
                <form method="POST" action="{{ route('gift-claim.store', $claimToken->token) }}" class="space-y-4">
                    @csrf
                    <button type="submit" class="vc-btn-primary w-full justify-center">
                        Claim gift
                    </button>
                </form>
            @else
                <p class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-700">
                    You are signed in as {{ auth()->user()->email }}. Sign in with {{ $giftPurchase->recipient_email }} to claim this gift.
                </p>
            @endif
        @else
            @if ($existingUser)
                <p class="rounded-md bg-slate-100 px-3 py-2 text-sm text-slate-700">
                    An account already exists for this recipient email. Sign in first, then come back to this claim link.
                </p>

                <a href="{{ route('login') }}" class="vc-btn-primary">
                    Sign in
                </a>
            @else
                <form method="POST" action="{{ route('gift-claim.store', $claimToken->token) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Name</label>
                        <input id="name" name="name" type="text" required value="{{ old('name') }}" class="vc-input">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Password</label>
                        <input id="password" name="password" type="password" required class="vc-input">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="vc-input">
                    </div>

                    <button type="submit" class="vc-btn-primary w-full justify-center">
                        Create account and claim gift
                    </button>
                </form>
            @endif
        @endauth
    </div>
</x-public-layout>
