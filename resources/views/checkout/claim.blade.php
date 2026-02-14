<x-public-layout>
    <x-slot:title>Claim Purchase</x-slot:title>

    <div class="mx-auto max-w-xl space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Claim Purchase</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $order->items->first()?->course?->title ?? 'Course access' }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                Purchase email: <span class="font-medium text-slate-900">{{ $order->email }}</span>
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
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
                <p class="text-sm text-slate-600">Logged in with matching email. Confirm to attach this purchase to your account.</p>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Claim this purchase</button>
            </form>
        @elseif ($existingUser)
            <div class="space-y-3">
                <p class="text-sm text-slate-600">An account already exists for this email. Log in and reopen this claim link.</p>
                <a href="{{ route('login') }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Log in</a>
            </div>
        @else
            <form method="POST" action="{{ route('claim-purchase.store', $claimToken->token) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Password</label>
                    <input id="password" name="password" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>

                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Create account and claim</button>
            </form>
        @endif
    </div>
</x-public-layout>
