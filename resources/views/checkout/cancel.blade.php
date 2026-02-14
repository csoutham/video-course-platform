<x-public-layout>
    <x-slot:title>Checkout Canceled</x-slot:title>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6">
        <h1 class="text-xl font-semibold text-amber-900">Checkout canceled</h1>
        <p class="mt-2 text-sm text-amber-800">
            Your payment was not completed. You can return to the course and try again.
        </p>
        <a href="{{ route('courses.index') }}" class="mt-4 inline-block text-sm font-semibold text-amber-900 hover:text-amber-700">
            Back to courses
        </a>
    </div>
</x-public-layout>
