<x-public-layout>
    <x-slot:title>My Courses</x-slot>

    <div class="space-y-6">
        <div class="vc-heading-block">
            <p class="vc-eyebrow">Learner Library</p>
            <h1 class="vc-title">My courses</h1>
            <p class="vc-subtitle">Courses you can start or continue right now.</p>
        </div>

        @if ($courses->isEmpty())
            <div class="vc-panel border-dashed p-6 text-sm text-slate-600">
                <p class="font-medium text-slate-800">No courses in your library yet.</p>
                <p class="mt-1">Browse the catalog to get started, or claim a recent purchase from your email link.</p>
                <a href="{{ route('courses.index') }}" class="vc-btn-primary mt-4">Browse courses</a>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($courses as $course)
                    <article
                        class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <a href="{{ route('learn.show', ['course' => $course->slug]) }}" class="block">
                            <div class="relative aspect-video overflow-hidden bg-slate-900">
                                @if ($course->thumbnail_url)
                                    <img
                                        src="{{ $course->thumbnail_url }}"
                                        alt="{{ $course->title }} thumbnail"
                                        loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                                @else
                                    <div
                                        class="flex h-full w-full items-center justify-center bg-linear-to-br from-slate-800 via-cyan-900 to-slate-700 px-6 text-center text-xl font-semibold tracking-tight text-white">
                                        {{ $course->title }}
                                    </div>
                                @endif

                                <span
                                    class="absolute right-3 bottom-3 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    Owned
                                </span>
                            </div>
                        </a>

                        <div class="space-y-3 p-5">
                            <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $course->title }}</h2>
                            <p class="line-clamp-3 text-sm leading-relaxed text-slate-600">
                                {{ $course->description }}
                            </p>

                            <a
                                href="{{ route('learn.show', ['course' => $course->slug]) }}"
                                class="vc-btn-primary mt-1">
                                Continue learning
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-public-layout>
