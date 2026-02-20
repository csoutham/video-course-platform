@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Courses', 'route' => 'admin.courses.index', 'active' => 'admin.courses.*'],
        ['label' => 'Imports', 'route' => 'admin.imports.udemy.show', 'active' => 'admin.imports.*'],
        ['label' => 'Orders', 'route' => 'admin.orders.index', 'active' => 'admin.orders.*'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'active' => 'admin.users.*'],
        ['label' => 'Branding', 'route' => 'admin.branding.edit', 'active' => 'admin.branding.*'],
    ];

    if (Route::has('admin.billing.edit')) {
        $items[] = ['label' => 'Billing', 'route' => 'admin.billing.edit', 'active' => 'admin.billing.*'];
    }
@endphp

<nav class="space-y-1 p-3">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" class="va-side-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
