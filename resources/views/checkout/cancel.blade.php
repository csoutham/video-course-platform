<x-public-layout>
    <x-slot:title>Checkout Canceled</x-slot>

    <div class="vc-panel-soft border-amber-200 bg-amber-50 p-6">
        <h1 class="text-xl font-semibold text-amber-900">Checkout not completed</h1>
        <p class="mt-2 text-sm text-amber-800">
            No payment was taken. You can return to the course page and try again any time.
        </p>
        <a href="{{ route('courses.index') }}" class="vc-btn-primary mt-4">Browse courses</a>
    </div>
</x-public-layout>
