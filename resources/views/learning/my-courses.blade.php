<x-public-layout>
    <x-slot:title>My Courses</x-slot>

    <div class="space-y-6">
        <div class="vc-heading-block">
            <p class="vc-eyebrow">Learner Library</p>
            <h1 class="vc-title">My courses</h1>
            <p class="vc-subtitle">Courses currently available under your active entitlements.</p>
        </div>

        @if ($courses->isEmpty())
            <div class="vc-panel border-dashed p-6 text-sm text-slate-600">No active course access found yet.</div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($courses as $course)
                    <article class="vc-panel p-5">
                        <h2 class="vc-card-title text-lg">{{ $course->title }}</h2>
                        <p class="vc-card-copy mt-2 line-clamp-3">{{ $course->description }}</p>

                        <a href="{{ route('learn.show', ['course' => $course->slug]) }}" class="vc-btn-primary mt-4">
                            Continue learning
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-public-layout>
