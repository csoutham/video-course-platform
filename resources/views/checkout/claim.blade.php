<x-public-layout>
    <x-slot:title>Add Purchase to Account</x-slot>

    <div class="vc-panel mx-auto max-w-xl space-y-6 p-6">
        <div>
            <p class="vc-eyebrow">Secure Claim</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">
                {{ $order->items->first()?->course?->title ?? 'Course access' }}
            </h1>
            <p class="mt-2 text-sm text-slate-600">
                Purchased with:
                <span class="font-medium text-slate-900">{{ $order->email }}</span>
            </p>
        </div>

        @if ($errors->any())
            <div class="vc-alert vc-alert-error">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($authenticatedEmailMatches)
            <form method="POST" action="{{ route('claim-purchase.store', $claimToken->token) }}" class="space-y-4">
                @csrf
                <p class="text-sm text-slate-600">
                    You are signed in with the matching email. Confirm below to add this purchase to your account.
                </p>
                <button type="submit" class="vc-btn-primary">Add this purchase</button>
            </form>
        @elseif ($existingUser)
            <div class="space-y-3">
                <p class="text-sm text-slate-600">
                    An account already exists for this email. Sign in, then reopen this claim link.
                </p>
                <a href="{{ route('login') }}" class="vc-btn-primary">Sign in</a>
            </div>
        @else
            <form method="POST" action="{{ route('claim-purchase.store', $claimToken->token) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="vc-label">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="vc-input" />
                </div>

                <div>
                    <label for="password" class="vc-label">Password</label>
                    <input id="password" name="password" type="password" required class="vc-input" />
                </div>

                <div>
                    <label for="password_confirmation" class="vc-label">Confirm password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        class="vc-input" />
                </div>

                <button type="submit" class="vc-btn-primary">Create account and claim</button>
            </form>
        @endif
    </div>
</x-public-layout>
