<x-public-layout>
    <x-slot:title>Checkout Success</x-slot:title>

    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6">
        <h1 class="text-xl font-semibold text-emerald-900">Payment received</h1>
        <p class="mt-2 text-sm text-emerald-800">
            Your order is being finalized. Access will appear in your account after webhook processing.
        </p>
        <a href="{{ route('courses.index') }}" class="mt-4 inline-block text-sm font-semibold text-emerald-900 hover:text-emerald-700">
            Back to courses
        </a>
    </div>
</x-public-layout>
