<x-public-layout title="Edit Course">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Edit Course</h1>
                <p class="vc-subtitle">Manage course pricing, publishing, and Stripe mapping.</p>
            </div>
            <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Back to Courses</a>
        </div>
    </section>

    @if (session('status'))
        <section class="vc-panel mt-4 p-4 text-sm text-slate-700">
            {{ session('status') }}
        </section>
    @endif

    <section class="vc-panel mt-6 p-6">
        <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="text-sm font-medium text-slate-700">Title</label>
                <input id="title" name="title" value="{{ old('title', $course->title) }}" required class="vc-input" />
                @error('title')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="text-sm font-medium text-slate-700">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $course->slug) }}" required class="vc-input" />
                @error('slug')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="text-sm font-medium text-slate-700">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="vc-input">{{ old('description', $course->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="thumbnail_url" class="text-sm font-medium text-slate-700">Thumbnail URL</label>
                <input
                    id="thumbnail_url"
                    name="thumbnail_url"
                    value="{{ old('thumbnail_url', $course->thumbnail_url) }}"
                    class="vc-input" />
                @error('thumbnail_url')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="price_amount" class="text-sm font-medium text-slate-700">Price (cents)</label>
                    <input
                        id="price_amount"
                        name="price_amount"
                        type="number"
                        min="100"
                        required
                        value="{{ old('price_amount', $course->price_amount) }}"
                        class="vc-input" />
                    @error('price_amount')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="price_currency" class="text-sm font-medium text-slate-700">Currency</label>
                    <input
                        id="price_currency"
                        name="price_currency"
                        maxlength="3"
                        required
                        value="{{ old('price_currency', $course->price_currency) }}"
                        class="vc-input lowercase" />
                    @error('price_currency')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="stripe_price_id" class="text-sm font-medium text-slate-700">Stripe Price ID</label>
                    <input
                        id="stripe_price_id"
                        name="stripe_price_id"
                        value="{{ old('stripe_price_id', $course->stripe_price_id) }}"
                        class="vc-input" />
                    @error('stripe_price_id')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $course->is_published)) />
                    Published
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="refresh_stripe_price" value="1" @checked(old('refresh_stripe_price')) />
                    Create and assign a new Stripe price now
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button class="vc-btn-primary" type="submit">Save Course</button>
                <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Back</a>
            </div>
        </form>
    </section>

    <section class="vc-panel mt-6 p-6">
        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Modules and Lessons</h2>
        <p class="mt-2 text-sm text-slate-600">
            Module and lesson CRUD will appear here as the next admin rollout step.
        </p>
    </section>
</x-public-layout>
