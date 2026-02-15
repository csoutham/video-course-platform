<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Admin Courses">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Courses</h1>
                <p class="vc-subtitle">Create and manage courses, then drill into modules and lessons.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.courses.create') }}" class="vc-btn-primary">Create Course</a>
                <a href="{{ route('admin.dashboard') }}" class="vc-btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </section>

    <section class="vc-panel mt-6 overflow-hidden">
        @if ($courses->isEmpty())
            <p class="p-6 text-sm text-slate-600">No courses found.</p>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Published</th>
                        <th class="px-4 py-3">Modules</th>
                        <th class="px-4 py-3">Lessons</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach ($courses as $course)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $course->title }}</td>
                            <td class="px-4 py-3">{{ $course->slug }}</td>
                            <td class="px-4 py-3">
                                {{ strtoupper($course->price_currency) }}
                                {{ number_format($course->price_amount / 100, 2) }}
                            </td>
                            <td class="px-4 py-3">{{ $course->is_published ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-3">{{ $course->modules_count }}</td>
                            <td class="px-4 py-3">{{ $course->lessons_count }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="vc-link">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    @if ($courses->hasPages())
        <section class="mt-4">
            {{ $courses->links() }}
        </section>
    @endif
</x-public-layout>
