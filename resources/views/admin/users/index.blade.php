<x-admin-layout maxWidth="max-w-none" containerPadding="px-4 py-6" title="Admin Users">
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

    <section class="vc-panel mt-6 p-6">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(360px,420px)]">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">User Directory</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Search is not available yet, but you can inspect learner history and add new platform users here.
                </p>
            </div>

            <aside class="vc-panel-soft p-4">
                <h2 class="text-sm font-semibold tracking-[0.12em] text-slate-600 uppercase">Create User</h2>
                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="vc-label">Name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="vc-input" />
                        @error('name')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="vc-label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="vc-input" />
                        @error('email')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="vc-label">Password</label>
                        <input id="password" name="password" type="password" required class="vc-input" />
                        <p class="vc-help">Must be at least 8 characters and pass the uncompromised-password check.</p>
                        @error('password')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="vc-label">Confirm password</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            class="vc-input" />
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            class="vc-checkbox"
                            type="checkbox"
                            name="is_admin"
                            value="1"
                            @checked(old('is_admin')) />
                        Grant admin access
                    </label>

                    <button type="submit" class="vc-btn-primary w-full justify-center">Create User</button>
                </form>
            </aside>
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
                        <th class="px-4 py-3">Role</th>
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
                            <td class="px-4 py-3">
                                <span
                                    class="{{ $user->is_admin ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                                    {{ $user->is_admin ? 'Admin' : 'Learner' }}
                                </span>
                            </td>
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
</x-admin-layout>
