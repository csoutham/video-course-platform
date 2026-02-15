<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Admin Users">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Users</h1>
                <p class="vc-subtitle">Inspect learner accounts, purchases, and progress history.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="vc-btn-secondary">Back to Dashboard</a>
        </div>
    </section>

    <section class="vc-panel mt-6 overflow-hidden">
        @if ($users->isEmpty())
            <p class="p-6 text-sm text-slate-600">No users found.</p>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Orders</th>
                        <th class="px-4 py-3">Active Courses</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->orders_count }}</td>
                            <td class="px-4 py-3">{{ $user->active_entitlements_count }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.users.show', $user) }}" class="vc-link">View Progress</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    @if ($users->hasPages())
        <section class="mt-4">
            {{ $users->links() }}
        </section>
    @endif
</x-public-layout>
