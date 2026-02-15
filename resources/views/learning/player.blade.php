<x-public-layout>
    <x-slot:title>{{ $course->title }} - Learn</x-slot:title>

    <div class="grid gap-6 lg:grid-cols-[300px_1fr]">
        <aside class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Course</p>
                <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ $course->title }}</h1>
            </div>

            <div class="space-y-4">
                @foreach ($course->modules as $module)
                    <section class="space-y-2">
                        <h2 class="text-sm font-semibold text-slate-800">{{ $module->title }}</h2>

                        @if ($module->lessons->isEmpty())
                            <p class="text-xs text-slate-500">No published lessons.</p>
                        @else
                            <ul class="space-y-1">
                                @foreach ($module->lessons as $lesson)
                                    @php
                                        $lessonProgress = $progressByLessonId->get($lesson->id);
                                        $isCompleted = $lessonProgress?->status === 'completed';
                                    @endphp
                                    <li>
                                        <a
                                            href="{{ route('learn.show', ['course' => $course->slug, 'lessonSlug' => $lesson->slug]) }}"
                                            class="flex items-center justify-between rounded-md px-2 py-1 text-sm {{ $activeLesson->id === $lesson->id ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                                        >
                                            <span>{{ $lesson->title }}</span>
                                            @if ($isCompleted)
                                                <span class="text-xs font-semibold {{ $activeLesson->id === $lesson->id ? 'text-emerald-200' : 'text-emerald-700' }}">
                                                    Completed
                                                </span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        </aside>

        <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Lesson</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $activeLesson->title }}</h2>
                @if ($activeLesson->summary)
                    <p class="mt-2 text-sm text-slate-600">{{ $activeLesson->summary }}</p>
                @endif

                <div class="mt-3">
                    @if (($activeLessonProgress->status ?? null) === 'completed')
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Lesson completed
                        </span>
                    @else
                        <form method="POST" action="{{ route('learn.progress.complete', ['course' => $course->slug, 'lessonSlug' => $activeLesson->slug]) }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                            >
                                Mark as complete
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="aspect-video w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                @if ($streamEmbedUrl)
                    <iframe
                        src="{{ $streamEmbedUrl }}"
                        class="h-full w-full"
                        allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;"
                        allowfullscreen
                    ></iframe>
                @else
                    <div class="flex h-full items-center justify-center px-6 text-sm text-slate-500">
                        Video not configured for this lesson yet.
                    </div>
                @endif
            </div>

            <div class="space-y-2">
                <h3 class="text-sm font-semibold text-slate-900">Resources</h3>

                @if ($activeLesson->resources->isEmpty())
                    <p class="text-sm text-slate-500">No resources attached to this lesson.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($activeLesson->resources as $resource)
                            <li>
                                <a
                                    href="{{ route('resources.download', $resource) }}"
                                    class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                                >
                                    Download {{ $resource->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
</x-public-layout>
