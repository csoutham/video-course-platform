<x-public-layout>
    <x-slot:title>My Courses</x-slot:title>

    <div class="space-y-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Learner Library</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">My courses</h1>
            <p class="mt-2 text-sm text-slate-600">Courses currently available under your active entitlements.</p>
        </div>

        @if ($courses->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600">
                No active course access found yet.
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($courses as $course)
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $course->title }}</h2>
                        <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $course->description }}</p>

                        <a
                            href="{{ route('learn.show', ['course' => $course->slug]) }}"
                            class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                            Continue learning
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-public-layout>
