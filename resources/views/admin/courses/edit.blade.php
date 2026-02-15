<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Edit Course">
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
                <textarea id="description" name="description" rows="4" class="vc-input">
{{ old('description', $course->description) }}</textarea
                >
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
                    <select id="price_currency" name="price_currency" required class="vc-input">
                        <option value="usd" @selected(old('price_currency', $course->price_currency) === 'usd')>
                            USD
                        </option>
                        <option value="gbp" @selected(old('price_currency', $course->price_currency) === 'gbp')>
                            GBP
                        </option>
                    </select>
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
                    <input
                        type="checkbox"
                        name="is_published"
                        value="1"
                        @checked(old('is_published', $course->is_published)) />
                    Published
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="refresh_stripe_price"
                        value="1"
                        @checked(old('refresh_stripe_price')) />
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
        <p class="mt-2 text-sm text-slate-600">Create modules and lessons directly from this screen.</p>

        <form
            action="{{ route('admin.modules.store', $course) }}"
            method="POST"
            class="mt-5 grid gap-3 sm:grid-cols-8">
            @csrf
            <div class="sm:col-span-5">
                <label class="text-sm font-medium text-slate-700">New module title</label>
                <input name="title" class="vc-input" placeholder="Module title" required />
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-slate-700">Sort order</label>
                <input
                    type="number"
                    min="0"
                    name="sort_order"
                    value="{{ old('sort_order', ($course->modules->max('sort_order') ?? 0) + 1) }}"
                    class="vc-input" />
            </div>
            <div class="flex items-end sm:col-span-1">
                <button type="submit" class="vc-btn-primary w-full justify-center">Add</button>
            </div>
        </form>

        @if ($streamCatalogStatus)
            <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                Cloudflare Stream list unavailable: {{ $streamCatalogStatus }}
            </div>
        @endif

        <div class="mt-6 space-y-6">
            @forelse ($course->modules as $module)
                <article class="vc-panel-soft p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <form
                            method="POST"
                            action="{{ route('admin.modules.update', $module) }}"
                            class="grid flex-1 gap-3 sm:grid-cols-6">
                            @csrf
                            @method('PUT')
                            <div class="sm:col-span-4">
                                <label class="text-sm font-medium text-slate-700">Module title</label>
                                <input name="title" value="{{ $module->title }}" class="vc-input" required />
                            </div>
                            <div class="sm:col-span-1">
                                <label class="text-sm font-medium text-slate-700">Sort</label>
                                <input
                                    type="number"
                                    min="0"
                                    name="sort_order"
                                    value="{{ $module->sort_order }}"
                                    class="vc-input"
                                    required />
                            </div>
                            <div class="flex items-end sm:col-span-1">
                                <button type="submit" class="vc-btn-secondary w-full justify-center">Save</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.modules.destroy', $module) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="vc-btn-secondary">Delete module</button>
                        </form>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.lessons.store', $module) }}"
                        class="mt-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-12">
                        @csrf
                        <div class="sm:col-span-3">
                            <label class="text-sm font-medium text-slate-700">Lesson title</label>
                            <input name="title" class="vc-input" required />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-sm font-medium text-slate-700">Slug (optional)</label>
                            <input name="slug" class="vc-input" />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="text-sm font-medium text-slate-700">Cloudflare Stream video</label>
                            <select name="stream_video_id" class="vc-input">
                                <option value="">No video yet</option>
                                @foreach ($streamVideos as $video)
                                    <option value="{{ $video['uid'] }}">
                                        {{ $video['name'] }} ({{ $video['uid'] }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">
                                Selecting a video enforces signed URLs and syncs duration from Cloudflare.
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-sm font-medium text-slate-700">Sort</label>
                            <input
                                type="number"
                                min="0"
                                name="sort_order"
                                value="{{ ($module->lessons->max('sort_order') ?? 0) + 1 }}"
                                class="vc-input" />
                        </div>
                        <div class="flex items-end sm:col-span-1">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_published" value="1" />
                                Live
                            </label>
                        </div>
                        <div class="flex items-end sm:col-span-1">
                            <button type="submit" class="vc-btn-primary w-full justify-center">Add</button>
                        </div>
                        <div class="sm:col-span-12">
                            <label class="text-sm font-medium text-slate-700">Summary</label>
                            <textarea name="summary" rows="2" class="vc-input"></textarea>
                        </div>
                    </form>

                    <div class="mt-4 space-y-3">
                        @forelse ($module->lessons as $lesson)
                            <form
                                method="POST"
                                action="{{ route('admin.lessons.update', $lesson) }}"
                                class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-12">
                                @csrf
                                @method('PUT')
                                <div class="sm:col-span-3">
                                    <label class="text-sm font-medium text-slate-700">Title</label>
                                    <input name="title" value="{{ $lesson->title }}" class="vc-input" required />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-sm font-medium text-slate-700">Slug</label>
                                    <input name="slug" value="{{ $lesson->slug }}" class="vc-input" required />
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="text-sm font-medium text-slate-700">Stream video</label>
                                    <select name="stream_video_id" class="vc-input">
                                        <option value="">No video</option>
                                        @if ($lesson->stream_video_id)
                                            <option value="{{ $lesson->stream_video_id }}" selected>
                                                Current: {{ $lesson->stream_video_id }}
                                            </option>
                                        @endif

                                        @foreach ($streamVideos as $video)
                                            <option
                                                value="{{ $video['uid'] }}"
                                                @selected($lesson->stream_video_id === $video['uid'])>
                                                {{ $video['name'] }} ({{ $video['uid'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Saving with a selected video enforces signed URLs and refreshes duration.
                                    </p>
                                </div>
                                <div class="sm:col-span-1">
                                    <label class="text-sm font-medium text-slate-700">Sort</label>
                                    <input
                                        type="number"
                                        min="0"
                                        name="sort_order"
                                        value="{{ $lesson->sort_order }}"
                                        class="vc-input"
                                        required />
                                </div>
                                <div class="sm:col-span-1">
                                    <label class="text-sm font-medium text-slate-700">Duration</label>
                                    <input
                                        type="number"
                                        min="0"
                                        name="duration_seconds"
                                        value="{{ $lesson->duration_seconds }}"
                                        class="vc-input" />
                                </div>
                                <div class="flex items-end sm:col-span-1">
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="is_published"
                                            value="1"
                                            @checked($lesson->is_published) />
                                        Live
                                    </label>
                                </div>
                                <div class="flex items-end sm:col-span-1">
                                    <span class="text-xs text-slate-500">Auto sync</span>
                                </div>
                                <div class="sm:col-span-9">
                                    <label class="text-sm font-medium text-slate-700">Summary</label>
                                    <textarea name="summary" rows="2" class="vc-input">
{{ $lesson->summary }}</textarea
                                    >
                                </div>
                                <div class="flex items-end sm:col-span-2">
                                    <button type="submit" class="vc-btn-secondary w-full justify-center">
                                        Save lesson
                                    </button>
                                </div>
                            </form>
                            <form
                                method="POST"
                                action="{{ route('admin.lessons.destroy', $lesson) }}"
                                class="mt-2 flex justify-end">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="vc-btn-secondary">Delete lesson</button>
                            </form>
                        @empty
                            <p class="text-sm text-slate-600">No lessons in this module yet.</p>
                        @endforelse
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-600">No modules yet. Create the first module above.</p>
            @endforelse
        </div>
    </section>
</x-public-layout>
