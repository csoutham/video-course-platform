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
                    @endphp
                    <article class="vc-panel-soft p-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">{{ $entitlement->course?->title ?? 'Unknown course' }}</h3>
                                <p class="mt-1 text-xs text-slate-600 uppercase">
                                    Entitlement: {{ $entitlement->status }}
                                    @if ($entitlement->granted_at)
                                        · Granted {{ $entitlement->granted_at->format('Y-m-d H:i') }}
                                    @endif
                                </p>
                            </div>
                            @if ($entitlement->order)
                                <p class="text-xs text-slate-600">
                                    Order #{{ $entitlement->order->id }} · {{ strtoupper($entitlement->order->currency) }}
                                    {{ number_format($entitlement->order->total_amount / 100, 2) }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">Lessons</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $progress['total_lessons'] }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">Completed</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $progress['completed_lessons'] }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">In Progress</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $progress['in_progress_lessons'] }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">Tracked</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $progress['tracked_lessons'] }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <p class="text-xs text-slate-500 uppercase">Avg Video Progress</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $progress['avg_percent'] }}%</p>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-slate-600">
                            Last viewed:
                            {{ $progress['last_viewed_at'] ? $progress['last_viewed_at']->format('Y-m-d H:i') : 'No activity yet' }}
                        </p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-public-layout>
