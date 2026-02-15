<x-public-layout title="Create Course">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Create Course</h1>
                <p class="vc-subtitle">Create a course and provision a Stripe one-time price automatically.</p>
            </div>
            <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Back to Courses</a>
        </div>
    </section>

    <section class="vc-panel mt-6 p-6">
        <form method="POST" action="{{ route('admin.courses.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="title" class="text-sm font-medium text-slate-700">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required class="vc-input" />
                @error('title')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="text-sm font-medium text-slate-700">Slug (optional)</label>
                <input id="slug" name="slug" value="{{ old('slug') }}" class="vc-input" />
                @error('slug')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="text-sm font-medium text-slate-700">Description</label>
                <textarea id="description" name="description" rows="4" class="vc-input">
{{ old('description') }}</textarea
                >
                @error('description')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="thumbnail_url" class="text-sm font-medium text-slate-700">Thumbnail URL</label>
                <input id="thumbnail_url" name="thumbnail_url" value="{{ old('thumbnail_url') }}" class="vc-input" />
                @error('thumbnail_url')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="price_amount" class="text-sm font-medium text-slate-700">Price (cents)</label>
                    <input
                        id="price_amount"
                        name="price_amount"
                        type="number"
                        min="100"
                        required
                        value="{{ old('price_amount', 9900) }}"
                        class="vc-input" />
                    @error('price_amount')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="price_currency" class="text-sm font-medium text-slate-700">Currency</label>
                    <select id="price_currency" name="price_currency" required class="vc-input">
                        <option value="usd" @selected(old('price_currency', 'usd') === 'usd')>USD</option>
                        <option value="gbp" @selected(old('price_currency', 'usd') === 'gbp')>GBP</option>
                    </select>
                    @error('price_currency')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published')) />
                    Publish course now
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="auto_create_stripe_price"
                        value="1"
                        @checked(old('auto_create_stripe_price', '1')) />
                    Auto-create Stripe price
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button class="vc-btn-primary" type="submit">Create Course</button>
                <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
</x-public-layout>
