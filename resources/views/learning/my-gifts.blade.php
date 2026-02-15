<x-public-layout>
    <x-slot:title>My Gifts</x-slot:title>

    <div class="space-y-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Gifts</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">My Gifts</h1>
            <p class="mt-2 text-sm text-slate-600">Track gifts you have purchased and their claim status.</p>
        </div>

        @if ($gifts->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600">
                You have not sent any gifts yet.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($gifts as $gift)
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-slate-900">{{ $gift->course->title ?? 'Course #'.$gift->course_id }}</p>
                                <p class="text-xs text-slate-500">Recipient: {{ $gift->recipient_email }}</p>
                                <p class="text-xs text-slate-500">Status: <span class="font-semibold">{{ ucfirst($gift->status) }}</span></p>
                            </div>
                            <p class="text-xs text-slate-500">{{ $gift->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-public-layout>

