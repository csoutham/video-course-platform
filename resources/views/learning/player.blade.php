<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-10">
    <x-slot:title>{{ $course->title }} - Learn</x-slot>
    @php
        $lessonSummaryHtml = $activeLesson->summary
            ? \Illuminate\Support\Str::markdown($activeLesson->summary, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
            : null;
        $formatRuntime = static function (?int $durationSeconds): ?string {
            if (!is_int($durationSeconds) || $durationSeconds <= 0) {
                return null;
            }

            $hours = intdiv($durationSeconds, 3600);
            $minutes = intdiv($durationSeconds % 3600, 60);
            $seconds = $durationSeconds % 60;

            if ($hours > 0) {
                return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
            }

            return sprintf('%d:%02d', $minutes, $seconds);
        };
    @endphp

    <div class="grid gap-5 lg:grid-cols-[340px_1fr] lg:gap-6">
        <aside
            class="order-2 space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:order-1 lg:sticky lg:top-24 lg:max-h-[calc(100vh-7.5rem)] lg:overflow-y-auto">
            <div>
                <p class="text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase">Course</p>
                <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ $course->title }}</h1>
            </div>

            <div class="space-y-4">
                @foreach ($course->modules as $module)
                    <section class="space-y-2">
                        <h2 class="text-sm font-semibold text-slate-800">{{ $module->title }}</h2>

                        @if ($module->lessons->isEmpty())
                            <p class="text-xs text-slate-500">No lessons are live in this module yet.</p>
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
                                            class="{{ $activeLesson->id === $lesson->id ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }} flex items-center justify-between rounded-md px-2 py-1 text-sm">
                                            <span>{{ $lesson->title }}</span>
                                            <span class="flex items-center gap-2">
                                                @if ($formatRuntime($lesson->duration_seconds))
                                                    <span
                                                        class="{{ $activeLesson->id === $lesson->id ? 'text-slate-200' : 'text-slate-500' }} text-xs tabular-nums">
                                                        {{ $formatRuntime($lesson->duration_seconds) }}
                                                    </span>
                                                @endif

                                                @if ($isCompleted)
                                                    <span
                                                        class="{{ $activeLesson->id === $lesson->id ? 'text-emerald-200' : 'text-emerald-700' }} text-xs font-semibold">
                                                        Completed
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        </aside>

        <section class="order-1 space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:order-2">
            <div>
                <p class="text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase">Lesson</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $activeLesson->title }}</h2>
            </div>

            <div class="aspect-video w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                @if ($streamEmbedUrl)
                    <iframe
                        id="stream-player"
                        src="{{ $streamEmbedUrl }}"
                        class="h-full w-full"
                        allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen></iframe>
                @else
                    <div class="flex h-full items-center justify-center px-6 text-sm text-slate-500">
                        This lesson video is not available yet.
                    </div>
                @endif
            </div>

            @if ($lessonSummaryHtml)
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Summary</h3>
                    <div class="prose prose-slate mt-2 max-w-none text-sm">
                        {!! $lessonSummaryHtml !!}
                    </div>
                </div>
            @endif

            @if ($courseResources->isNotEmpty() || $moduleResources->isNotEmpty() || $lessonResources->isNotEmpty())
                <div class="space-y-2">
                    <h3 class="text-sm font-semibold text-slate-900">Resources</h3>
                    @if ($courseResources->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                Course resources
                            </p>
                            <ul class="space-y-2">
                                @foreach ($courseResources as $resource)
                                    <li>
                                        <a
                                            href="{{ route('resources.download', $resource) }}"
                                            class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                            Download {{ $resource->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($moduleResources->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                Module resources
                            </p>
                            <ul class="space-y-2">
                                @foreach ($moduleResources as $resource)
                                    <li>
                                        <a
                                            href="{{ route('resources.download', $resource) }}"
                                            class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                            Download {{ $resource->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($lessonResources->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                Lesson resources
                            </p>
                            <ul class="space-y-2">
                                @foreach ($lessonResources as $resource)
                                    <li>
                                        <a
                                            href="{{ route('resources.download', $resource) }}"
                                            class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                            Download {{ $resource->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-2 items-center gap-3 border-t border-slate-200 pt-4 sm:grid-cols-3">
                <div class="justify-self-start">
                    @if ($previousLesson)
                        <a
                            href="{{ route('learn.show', ['course' => $course->slug, 'lessonSlug' => $previousLesson->slug]) }}"
                            class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                            Previous lesson
                        </a>
                    @else
                        <span class="text-sm text-slate-400">Previous lesson</span>
                    @endif
                </div>

                <div class="order-3 col-span-2 justify-self-center sm:order-2 sm:col-span-1">
                    <form
                        method="POST"
                        action="{{ route('learn.progress.complete', ['course' => $course->slug, 'lessonSlug' => $activeLesson->slug]) }}">
                        @csrf
                        <button
                            type="submit"
                            class="{{ ($activeLessonProgress->status ?? null) === 'completed' ? 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} inline-flex items-center rounded-md border px-3 py-2 text-sm font-semibold">
                            {{ ($activeLessonProgress->status ?? null) === 'completed' ? 'Mark as incomplete' : 'Mark as complete' }}
                        </button>
                    </form>
                </div>

                <div class="justify-self-end sm:order-3">
                    @if ($nextLesson)
                        <a
                            href="{{ route('learn.show', ['course' => $course->slug, 'lessonSlug' => $nextLesson->slug]) }}"
                            class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Next lesson
                        </a>
                    @else
                        <span class="text-sm text-slate-400">Next lesson</span>
                    @endif
                </div>
            </div>
        </section>
    </div>

    @if ($streamEmbedUrl)
        <script src="https://embed.videodelivery.net/embed/sdk.latest.js"></script>
        <script>
            (() => {
                const iframe = document.getElementById('stream-player');
                if (!iframe || typeof window.Stream !== 'function') {
                    return;
                }

                const player = window.Stream(iframe);
                const heartbeatSeconds = {{ $videoProgressHeartbeatSeconds }};
                const resumeSeconds = {{ max(0, (int) ($activeLessonProgress->playback_position_seconds ?? 0)) }};
                const endpoint = @json(route('learn.progress.video', ['course' => $course->slug, 'lessonSlug' => $activeLesson->slug]));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                let lastSentAt = 0;
                let heartbeatTimer = null;
                let resumeApplied = false;

                const readMetrics = () => {
                    const rawPosition = Number(player.currentTime ?? 0);
                    const rawDuration = Number(player.duration ?? 0);

                    return {
                        position_seconds: Math.max(0, Math.floor(Number.isFinite(rawPosition) ? rawPosition : 0)),
                        duration_seconds:
                            Number.isFinite(rawDuration) && rawDuration > 0
                                ? Math.max(1, Math.floor(rawDuration))
                                : null,
                    };
                };

                const sendHeartbeat = (payload = {}) => {
                    const now = Date.now();

                    if (!payload.is_completed && now - lastSentAt < heartbeatSeconds * 1000) {
                        return;
                    }

                    lastSentAt = now;

                    fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                        keepalive: true,
                        body: JSON.stringify(payload),
                    }).catch(() => {
                        // Do not interrupt playback if telemetry call fails.
                    });
                };

                const startHeartbeat = () => {
                    if (heartbeatTimer) {
                        return;
                    }

                    heartbeatTimer = window.setInterval(() => {
                        sendHeartbeat({
                            ...readMetrics(),
                            is_completed: false,
                        });
                    }, heartbeatSeconds * 1000);
                };

                const stopHeartbeat = () => {
                    if (!heartbeatTimer) {
                        return;
                    }

                    window.clearInterval(heartbeatTimer);
                    heartbeatTimer = null;
                };

                const applyResumeAndAutoplay = () => {
                    if (resumeApplied) {
                        return;
                    }

                    resumeApplied = true;

                    if (resumeSeconds > 0) {
                        try {
                            player.currentTime = resumeSeconds;
                        } catch (_) {
                            // Ignore resume failures; playback can continue from start.
                        }
                    }
                };

                player.addEventListener('play', () => {
                    startHeartbeat();
                });

                player.addEventListener('pause', () => {
                    stopHeartbeat();

                    sendHeartbeat({
                        ...readMetrics(),
                        is_completed: false,
                    });
                });

                player.addEventListener('ended', () => {
                    stopHeartbeat();

                    sendHeartbeat({
                        ...readMetrics(),
                        is_completed: true,
                    });
                });

                player.addEventListener('loadedmetadata', () => {
                    applyResumeAndAutoplay();
                });

                window.setTimeout(() => {
                    applyResumeAndAutoplay();
                }, 1200);

                window.addEventListener('beforeunload', () => {
                    stopHeartbeat();

                    sendHeartbeat({
                        ...readMetrics(),
                        is_completed: false,
                    });
                });
            })();
        </script>
    @endif
</x-public-layout>
