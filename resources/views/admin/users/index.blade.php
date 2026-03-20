@php
    $showCreateUserModal = $errors->hasAny(['name', 'email', 'password', 'password_confirmation']);
@endphp

<x-admin-layout maxWidth="max-w-none" containerPadding="px-4 py-6" title="Admin Users">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Users</h1>
                <p class="vc-subtitle">Inspect learner accounts, purchases, and progress history.</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="vc-btn-primary"
                    data-user-create-open>
                    New User
                </button>
                <a href="{{ route('admin.dashboard') }}" class="vc-btn-secondary">Back to Dashboard</a>
            </div>
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

    <div
        class="{{ $showCreateUserModal ? '' : 'pointer-events-none' }} fixed inset-0 z-[70] overflow-y-auto px-4 py-6 sm:px-0"
        data-user-create-modal
        aria-hidden="{{ $showCreateUserModal ? 'false' : 'true' }}">
        <div
            class="absolute inset-0 bg-slate-900/50 transition-opacity {{ $showCreateUserModal ? 'opacity-100' : 'opacity-0' }}"
            data-user-create-backdrop></div>

        <div
            class="sm:max-w-xl relative mb-6 transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:mx-auto sm:w-full {{ $showCreateUserModal ? 'translate-y-0 opacity-100 sm:scale-100' : 'translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95' }}"
            data-user-create-panel>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6 p-6">
            @csrf

            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Create User</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Create a learner or admin account without leaving the user directory.
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-md p-2 text-slate-500 transition hover:bg-slate-100"
                    data-user-create-close
                    aria-label="Close create user modal">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
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
            </div>

            <div class="grid gap-4 md:grid-cols-2">
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

            <div class="flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="vc-btn-secondary"
                    data-user-create-close>
                    Cancel
                </button>
                <button type="submit" class="vc-btn-primary">Create User</button>
            </div>
        </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.querySelector('[data-user-create-modal]');
            if (!modal) return;

            const panel = modal.querySelector('[data-user-create-panel]');
            const backdrop = modal.querySelector('[data-user-create-backdrop]');
            const openButtons = document.querySelectorAll('[data-user-create-open]');
            const closeButtons = modal.querySelectorAll('[data-user-create-close]');

            const open = () => {
                modal.classList.remove('pointer-events-none');
                modal.setAttribute('aria-hidden', 'false');
                backdrop?.classList.remove('opacity-0');
                backdrop?.classList.add('opacity-100');
                panel?.classList.remove('translate-y-4', 'opacity-0', 'sm:scale-95');
                panel?.classList.add('translate-y-0', 'opacity-100', 'sm:scale-100');
                document.body.classList.add('overflow-y-hidden');
                modal.querySelector('input, button, select, textarea')?.focus();
            };

            const close = () => {
                backdrop?.classList.add('opacity-0');
                backdrop?.classList.remove('opacity-100');
                panel?.classList.add('translate-y-4', 'opacity-0', 'sm:scale-95');
                panel?.classList.remove('translate-y-0', 'opacity-100', 'sm:scale-100');
                modal.setAttribute('aria-hidden', 'true');
                window.setTimeout(() => modal.classList.add('pointer-events-none'), 200);
                document.body.classList.remove('overflow-y-hidden');
            };

            openButtons.forEach((button) => button.addEventListener('click', open));
            closeButtons.forEach((button) => button.addEventListener('click', close));
            backdrop?.addEventListener('click', close);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                    close();
                }
            });
        })();
    </script>
</x-admin-layout>
