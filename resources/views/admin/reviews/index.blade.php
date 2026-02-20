<x-admin-layout maxWidth="max-w-none" containerPadding="px-4 py-6" title="Admin Reviews">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Ratings and Reviews</h1>
                <p class="vc-subtitle">Moderate learner submissions and imported reviews in one queue.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="vc-btn-secondary">Back to Dashboard</a>
        </div>
    </section>

    <section class="vc-panel mt-6 p-4 sm:p-6">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="status" class="vc-label">Status</label>
                <select id="status" name="status" class="vc-input">
                    <option value="">All</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="source" class="vc-label">Source</label>
                <select id="source" name="source" class="vc-input">
                    <option value="">All</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source }}" @selected($selectedSource === $source)>{{ str($source)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="course_id" class="vc-label">Course</label>
                <select id="course_id" name="course_id" class="vc-input">
                    <option value="">All</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected($selectedCourseId === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2 lg:col-span-1">
                <label for="q" class="vc-label">Search</label>
                <input id="q" name="q" value="{{ $search }}" class="vc-input" placeholder="Name, title, body..." />
            </div>

            <div class="flex items-end gap-2">
                <button class="vc-btn-primary w-full justify-center" type="submit">Apply</button>
                <a href="{{ route('admin.reviews.index') }}" class="vc-btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="vc-panel mt-6 overflow-x-auto">
        @if ($reviews->isEmpty())
            <p class="p-6 text-sm text-slate-600">No reviews found for the current filters.</p>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Reviewer</th>
                        <th class="px-4 py-3">Rating</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Text</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach ($reviews as $review)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.courses.edit', $review->course_id) }}#reviews" class="vc-link">
                                    {{ $review->course?->title ?? 'Unknown course' }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @if ($review->source === \App\Models\CourseReview::SOURCE_NATIVE)
                                    {{ $review->user?->name ?? 'Unknown user' }}
                                    <p class="text-xs text-slate-500">{{ $review->user?->email }}</p>
                                @else
                                    {{ $review->reviewer_name }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $review->rating }}/5</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    {{ ucfirst($review->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs uppercase">
                                {{ str($review->source)->replace('_', ' ') }}
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ $review->title }}</p>
                                <p class="line-clamp-3 text-xs text-slate-600">{{ $review->body }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @if ($review->status !== \App\Models\CourseReview::STATUS_APPROVED)
                                        <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                                            @csrf
                                            <button class="vc-btn-secondary !px-2.5 !py-1.5 !text-xs" type="submit">
                                                Approve
                                            </button>
                                        </form>
                                    @endif

                                    @if ($review->status !== \App\Models\CourseReview::STATUS_REJECTED)
                                        <form method="POST" action="{{ route('admin.reviews.reject', $review) }}">
                                            @csrf
                                            <button class="vc-btn-secondary !px-2.5 !py-1.5 !text-xs" type="submit">
                                                Reject
                                            </button>
                                        </form>
                                    @endif

                                    @if ($review->status !== \App\Models\CourseReview::STATUS_HIDDEN)
                                        <form method="POST" action="{{ route('admin.reviews.hide', $review) }}">
                                            @csrf
                                            <button class="vc-btn-secondary !px-2.5 !py-1.5 !text-xs" type="submit">
                                                Hide
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.reviews.unhide', $review) }}">
                                            @csrf
                                            <button class="vc-btn-secondary !px-2.5 !py-1.5 !text-xs" type="submit">
                                                Unhide
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100"
                                            type="submit">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    @if ($reviews->hasPages())
        <section class="mt-4">
            {{ $reviews->links() }}
        </section>
    @endif
</x-admin-layout>

