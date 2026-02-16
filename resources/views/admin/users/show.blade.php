<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Admin User Progress">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">{{ $targetUser->name }}</h1>
                <p class="vc-subtitle">{{ $targetUser->email }}</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="vc-btn-secondary">Back to Users</a>
        </div>
    </section>

    <section class="vc-panel mt-6 p-6">
        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Purchased Courses and Progress</h2>

        @if ($entitlements->isEmpty())
            <p class="mt-3 text-sm text-slate-600">This user has no course entitlements yet.</p>
        @else
            <div class="mt-4 space-y-4">
                @foreach ($entitlements as $entitlement)
                    @php
                        $progress = $progressByCourse->get($entitlement->course_id, [
                            'total_lessons' => 0,
                            'tracked_lessons' => 0,
                            'completed_lessons' => 0,
                            'in_progress_lessons' => 0,
                            'avg_percent' => 0,
                            'last_viewed_at' => null,
                        ]);
                        $lessonLog = $progressLogByCourse->get($entitlement->course_id, collect());
                    @endphp

                    <article class="vc-panel-soft p-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    {{ $entitlement->course?->title ?? 'Unknown course' }}
                                </h3>
                                <p class="mt-1 text-xs text-slate-600 uppercase">
                                    Entitlement: {{ $entitlement->status }}
                                    @if ($entitlement->granted_at)
                                            · Granted {{ $entitlement->granted_at->format('Y-m-d H:i') }}
                                    @endif
                                </p>
                            </div>
                            @if ($entitlement->order)
                                <p class="text-xs text-slate-600">
                                    Order #{{ $entitlement->order->id }} ·
                                    {{ strtoupper($entitlement->order->currency) }}
                                    {{ number_format($entitlement->order->total_amount / 100, 2) }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">Lessons</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ $progress['total_lessons'] }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">Completed</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ $progress['completed_lessons'] }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">In Progress</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ $progress['in_progress_lessons'] }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">Tracked</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ $progress['tracked_lessons'] }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">Avg Video Progress</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ $progress['avg_percent'] }}%
                                </p>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-slate-600">
                            Last viewed:
                            {{ $progress['last_viewed_at'] ? $progress['last_viewed_at']->format('Y-m-d H:i') : 'No activity yet' }}
                        </p>

                        <div class="mt-4">
                            <h4 class="text-sm font-semibold tracking-wide text-slate-700 uppercase">
                                Lesson Activity Log
                            </h4>

                            @if ($lessonLog->isEmpty())
                                <p class="mt-2 text-sm text-slate-600">No watched lessons yet for this course.</p>
                            @else
                                <div class="mt-2 overflow-hidden rounded-xl border border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead
                                            class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                            <tr>
                                                <th class="px-3 py-2">Lesson</th>
                                                <th class="px-3 py-2">Status</th>
                                                <th class="px-3 py-2">Watched / Duration</th>
                                                <th class="px-3 py-2">Progress</th>
                                                <th class="px-3 py-2">Last Viewed</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                            @foreach ($lessonLog as $entry)
                                                <tr>
                                                    <td class="px-3 py-2 font-medium text-slate-900">
                                                        {{ $entry['lesson_title'] }}
                                                    </td>
                                                    <td class="px-3 py-2 uppercase">{{ $entry['status'] }}</td>
                                                    <td class="px-3 py-2">
                                                        {{ $entry['watched_seconds'] }}s /
                                                        {{ $entry['duration_seconds'] !== null ? $entry['duration_seconds'].'s' : 'n/a' }}
                                                    </td>
                                                    <td class="px-3 py-2">{{ $entry['percent_complete'] }}%</td>
                                                    <td class="px-3 py-2">
                                                        {{ $entry['last_viewed_at'] ? $entry['last_viewed_at']->format('Y-m-d H:i') : 'n/a' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-public-layout>
