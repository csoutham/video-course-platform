<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Edit Course">
    @php
        $moduleCount = $course->modules->count();
        $lessonCount = $course->modules->sum(fn ($module) => $module->lessons->count());
    @endphp

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

    <section
        class="sticky top-0 z-30 -mx-4 mt-6 border-y border-slate-200/90 bg-white/95 px-4 py-3 shadow-sm backdrop-blur lg:-mx-8 lg:px-8">
        <div class="mx-auto flex max-w-none flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1">
                <button
                    type="button"
                    data-admin-tab-button="details"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    Course Details
                </button>
                <button
                    type="button"
                    data-admin-tab-button="curriculum"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    Curriculum
                </button>
                <button
                    type="button"
                    data-admin-tab-button="assets"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    Assets
                </button>
            </div>

            <p class="text-xs text-slate-600" data-course-count-summary>
                {{ $moduleCount }} modules · {{ $lessonCount }} lessons
            </p>
        </div>
    </section>

    <section class="vc-panel mt-6 p-6" data-admin-tab-panel="details">
        <form
            method="POST"
            action="{{ route('admin.courses.update', $course) }}"
            enctype="multipart/form-data"
            class="space-y-5">
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
                <label for="description" class="text-sm font-medium text-slate-700">Subtitle</label>
                <textarea id="description" name="description" rows="4" class="vc-input">
{{ old('description', $course->description) }}
                </textarea>
                @error('description')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="long_description" class="text-sm font-medium text-slate-700">
                    Long description (Markdown)
                </label>
                <textarea id="long_description" name="long_description" rows="8" class="vc-input">
{{ old('long_description', $course->long_description) }}</textarea
                >
                @error('long_description')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="requirements" class="text-sm font-medium text-slate-700">Requirements (Markdown)</label>
                <textarea id="requirements" name="requirements" rows="6" class="vc-input">
{{ old('requirements', $course->requirements) }}</textarea
                >
                @error('requirements')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="thumbnail_image" class="text-sm font-medium text-slate-700">Thumbnail image</label>
                @if ($course->thumbnail_url)
                    <img
                        src="{{ $course->thumbnail_url }}"
                        alt="{{ $course->title }} thumbnail"
                        class="mt-2 h-28 w-full rounded-lg border border-slate-200 object-cover sm:w-56" />
                @endif
                <label
                    for="thumbnail_image"
                    class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-slate-400 hover:bg-slate-100">
                    <span class="text-sm font-semibold text-slate-700">Drop image here or click to replace</span>
                    <span class="mt-1 text-xs text-slate-500">JPG, PNG, WEBP up to 5MB</span>
                </label>
                <input id="thumbnail_image" name="thumbnail_image" type="file" accept="image/*" class="sr-only" />
                @error('thumbnail_image')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="intro_video_id" class="text-sm font-medium text-slate-700">
                    Intro video (Cloudflare Stream)
                </label>
                <input
                    type="text"
                    data-stream-search
                    class="vc-input mb-2"
                    placeholder="Search videos by name or UID"
                    autocomplete="off" />
                <select id="intro_video_id" name="intro_video_id" class="vc-input" data-stream-select>
                    <option value="">No intro video</option>
                    @if ($course->intro_video_id)
                        <option value="{{ $course->intro_video_id }}" selected>
                            Current: {{ $course->intro_video_id }}
                        </option>
                    @endif

                    @foreach ($streamVideos as $video)
                        <option
                            value="{{ $video['uid'] }}"
                            @selected(old('intro_video_id', $course->intro_video_id) === $video['uid'])>
                            {{ $video['name'] }} ({{ $video['uid'] }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">
                    Intro video appears on public course page and is forced to signed URLs on save.
                </p>
                @error('intro_video_id')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="stream_video_filter_term" class="text-sm font-medium text-slate-700">
                    Stream catalog filter term
                </label>
                <input
                    id="stream_video_filter_term"
                    name="stream_video_filter_term"
                    value="{{ old('stream_video_filter_term', $course->stream_video_filter_term) }}"
                    class="vc-input"
                    placeholder="e.g. Monologue Course" />
                <p class="mt-1 text-xs text-slate-500">
                    This controls which Cloudflare Stream videos are shown for lesson and intro-video selection.
                </p>
                @if (!empty($streamCatalogFilterNotice))
                    <p class="mt-1 text-xs text-sky-700">{{ $streamCatalogFilterNotice }}</p>
                @endif

                @error('stream_video_filter_term')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="price_amount" class="text-sm font-medium text-slate-700">Price (cents/pence)</label>
                    <input
                        id="price_amount"
                        name="price_amount"
                        type="number"
                        min="0"
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_free" value="1" @checked(old('is_free', $course->is_free)) />
                        Free course (lead magnet)
                    </label>
                    <p class="text-xs text-slate-500">
                        Free courses bypass Stripe checkout and use instant enrollment or claim links.
                    </p>
                </div>
                <div>
                    <label for="free_access_mode" class="text-sm font-medium text-slate-700">Free access mode</label>
                    <select id="free_access_mode" name="free_access_mode" class="vc-input">
                        <option
                            value="claim_link"
                            @selected(old('free_access_mode', $course->free_access_mode) === 'claim_link')>
                            Claim link
                        </option>
                        <option
                            value="direct"
                            @selected(old('free_access_mode', $course->free_access_mode) === 'direct')>
                            Direct grant
                        </option>
                    </select>
                    @error('free_access_mode')
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

    <section class="vc-panel mt-6 hidden p-6" data-admin-tab-panel="curriculum">
        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Modules and Lessons</h2>
        <p class="mt-2 text-sm text-slate-600">Create modules and lessons directly from this screen.</p>

        <form
            action="{{ route('admin.modules.store', $course) }}"
            method="POST"
            data-async-curriculum
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

        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-sm font-semibold text-slate-900">Course resources (PDF)</h3>
            <form
                method="POST"
                action="{{ route('admin.resources.course.store', $course) }}"
                enctype="multipart/form-data"
                data-async-curriculum
                data-async-success="Course resource uploaded."
                class="mt-3 grid gap-3 sm:grid-cols-6">
                @csrf
                <div class="sm:col-span-3">
                    <label class="text-xs font-semibold tracking-wide text-slate-600 uppercase">Display name</label>
                    <input name="name" class="vc-input !mt-0 py-1.5 text-sm" placeholder="Workbook.pdf" />
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold tracking-wide text-slate-600 uppercase">PDF file</label>
                    <input name="resource_file" type="file" accept="application/pdf" class="vc-input !mt-0 py-1.5 text-sm" required />
                </div>
                <div class="flex items-end sm:col-span-1">
                    <button type="submit" class="vc-btn-primary w-full justify-center">Upload</button>
                </div>
            </form>

            @if ($course->resources->isNotEmpty())
                <ul class="mt-3 space-y-2">
                    @foreach ($course->resources as $resource)
                        <li class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <span class="text-slate-700">{{ $resource->name }}</span>
                            <form
                                method="POST"
                                action="{{ route('admin.resources.destroy', $resource) }}"
                                data-async-curriculum
                                data-async-success="Resource deleted.">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                    title="Delete resource"
                                    aria-label="Delete resource">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4 fill-current">
                                        <path
                                            d="M9 3a1 1 0 0 0-1 1v1H5a1 1 0 1 0 0 2h1v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7h1a1 1 0 1 0 0-2h-3V4a1 1 0 0 0-1-1H9Zm2 2h2v1h-2V5Zm-3 2h8v12H8V7Z" />
                                    </svg>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($course->modules->isNotEmpty())
            <div class="mt-5 flex flex-wrap items-center justify-end gap-2">
                <button type="button" data-modules-expand-all class="vc-btn-secondary !px-3 !py-1.5 !text-xs">
                    Expand all modules
                </button>
                <button type="button" data-modules-collapse-all class="vc-btn-secondary !px-3 !py-1.5 !text-xs">
                    Collapse all modules
                </button>
            </div>
        @endif

        <div class="mt-6 space-y-6">
            @forelse ($course->modules as $module)
                @php
                    $moduleLessonCount = $module->lessons->count();
                    $moduleLiveLessonCount = $module->lessons->where('is_published', true)->count();
                @endphp

                <article
                    class="rounded-lg border border-slate-200 bg-slate-50/50 p-4 shadow-sm"
                    data-module-card
                    data-module-id="{{ $module->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <div
                                class="inline-flex h-7 items-center rounded-full bg-slate-200 px-2.5 text-[11px] font-semibold tracking-wide text-slate-700 uppercase">
                                Module
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-slate-900">{{ $module->title }}</h3>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                                    <span
                                        class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-0.5 font-medium text-slate-700">
                                        {{ $moduleLessonCount }} total lessons
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">
                                        {{ $moduleLiveLessonCount }} live
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                                        Sort {{ $module->sort_order }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button
                            type="button"
                            data-module-toggle
                            class="vc-btn-secondary !px-3 !py-1.5 !text-xs"
                            aria-expanded="true">
                            Collapse
                        </button>
                    </div>

                    <div data-module-content class="mt-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <form
                                method="POST"
                                action="{{ route('admin.modules.update', $module) }}"
                                data-async-curriculum
                                data-async-success="Module updated."
                                class="grid flex-1 gap-3 sm:grid-cols-7">
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
                                <div class="flex items-end sm:col-span-1">
                                    <button
                                        type="submit"
                                        form="delete-module-{{ $module->id }}"
                                        class="inline-flex h-10 w-full items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                        title="Delete module"
                                        aria-label="Delete module">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            class="h-4 w-4 fill-current">
                                            <path
                                                d="M9 3a1 1 0 0 0-1 1v1H5a1 1 0 1 0 0 2h1v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7h1a1 1 0 1 0 0-2h-3V4a1 1 0 0 0-1-1H9Zm2 2h2v1h-2V5Zm-3 2h8v12H8V7Zm2 2a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm4 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Z" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <form
                            id="delete-module-{{ $module->id }}"
                            method="POST"
                            action="{{ route('admin.modules.destroy', $module) }}"
                            data-async-curriculum
                            data-async-success="Module deleted."
                            class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3">
                            <h4 class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                Module resources (PDF)
                            </h4>
                            <form
                                method="POST"
                                action="{{ route('admin.resources.module.store', $module) }}"
                                enctype="multipart/form-data"
                                data-async-curriculum
                                data-async-success="Module resource uploaded."
                                class="mt-2 grid gap-3 sm:grid-cols-6">
                                @csrf
                                <div class="sm:col-span-3">
                                    <input
                                        name="name"
                                        class="vc-input !mt-0 py-1.5 text-sm"
                                        placeholder="Module handout.pdf" />
                                </div>
                                <div class="sm:col-span-2">
                                    <input
                                        name="resource_file"
                                        type="file"
                                        accept="application/pdf"
                                        class="vc-input !mt-0 py-1.5 text-sm"
                                        required />
                                </div>
                                <div class="flex items-end sm:col-span-1">
                                    <button type="submit" class="vc-btn-secondary w-full justify-center">Upload</button>
                                </div>
                            </form>
                            @if ($module->resources->isNotEmpty())
                                <ul class="mt-2 space-y-1">
                                    @foreach ($module->resources as $resource)
                                        <li class="flex items-center justify-between rounded-md border border-slate-200 px-2.5 py-1.5 text-xs">
                                            <span class="text-slate-700">{{ $resource->name }}</span>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.resources.destroy', $resource) }}"
                                                data-async-curriculum
                                                data-async-success="Resource deleted.">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                    title="Delete resource"
                                                    aria-label="Delete resource">
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        class="h-3.5 w-3.5 fill-current">
                                                        <path
                                                            d="M9 3a1 1 0 0 0-1 1v1H5a1 1 0 1 0 0 2h1v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7h1a1 1 0 1 0 0-2h-3V4a1 1 0 0 0-1-1H9Zm2 2h2v1h-2V5Zm-3 2h8v12H8V7Z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <form
                            method="POST"
                            action="{{ route('admin.lessons.store', $module) }}"
                            data-async-curriculum
                            data-async-success="Lesson added."
                            class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                            @csrf
                            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                            Lesson title
                                        </label>
                                        <input name="title" class="vc-input !mt-0 py-1.5 text-sm" required />
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                            Slug (optional)
                                        </label>
                                        <input name="slug" class="vc-input !mt-0 py-1.5 text-sm" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                            Sort
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            name="sort_order"
                                            value="{{ ($module->lessons->max('sort_order') ?? 0) + 1 }}"
                                            class="vc-input !mt-0 py-1.5 text-sm" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                            Cloudflare Stream video
                                        </label>
                                        <input
                                            type="text"
                                            data-stream-search
                                            class="vc-input !mt-0 mb-2 py-1.5 text-sm"
                                            placeholder="Search videos by name or UID"
                                            autocomplete="off" />
                                        <select
                                            name="stream_video_id"
                                            class="vc-input !mt-0 py-1.5 text-sm"
                                            data-stream-select>
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

                                    <div
                                        class="mt-1 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3 sm:col-span-2">
                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="checkbox" name="is_published" value="1" />
                                            Live
                                        </label>

                                        <button type="submit" class="vc-btn-primary">Add lesson</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                        Summary
                                    </label>
                                    <p class="mb-2 text-xs text-slate-500">Supports basic Markdown.</p>
                                    <textarea name="summary" rows="8" class="vc-input !mt-0 text-sm"></textarea>
                                </div>
                            </div>
                        </form>

                        <div class="mt-4 space-y-3">
                            @forelse ($module->lessons as $lesson)
                                <article class="rounded-xl border border-blue-200/50 bg-blue-50/50 p-4 shadow-xs">
                                    <div
                                        class="mb-3 inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold tracking-wide text-blue-600 uppercase">
                                        Lesson
                                    </div>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.lessons.update', $lesson) }}"
                                        data-async-curriculum
                                        data-async-success="Lesson saved."
                                        class="space-y-4">
                                        @csrf
                                        @method('PUT')

                                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
                                            <div class="grid gap-3 sm:grid-cols-6">
                                                <div class="sm:col-span-6">
                                                    <label
                                                        class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                        Lesson title
                                                    </label>
                                                    <input
                                                        name="title"
                                                        value="{{ $lesson->title }}"
                                                        class="vc-input !mt-0 py-1.5 text-sm"
                                                        required />
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label
                                                        class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                        Slug
                                                    </label>
                                                    <input
                                                        name="slug"
                                                        value="{{ $lesson->slug }}"
                                                        class="vc-input !mt-0 py-1.5 text-sm"
                                                        required />
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label
                                                        class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                        Sort
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        name="sort_order"
                                                        value="{{ $lesson->sort_order }}"
                                                        class="vc-input !mt-0 py-1.5 text-sm"
                                                        required />
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label
                                                        class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                        Duration (seconds)
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        name="duration_seconds"
                                                        value="{{ $lesson->duration_seconds }}"
                                                        class="vc-input !mt-0 py-1.5 text-sm" />
                                                </div>
                                                <div class="sm:col-span-6">
                                                    <label
                                                        class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                        Module
                                                    </label>
                                                    <select name="module_id" class="vc-input !mt-0 py-1.5 text-sm">
                                                        @foreach ($course->modules as $moduleOption)
                                                            <option
                                                                value="{{ $moduleOption->id }}"
                                                                @selected($lesson->module_id === $moduleOption->id)>
                                                                {{ $moduleOption->title }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="sm:col-span-6">
                                                    <label
                                                        class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                        Stream video
                                                    </label>
                                                    <input
                                                        type="text"
                                                        data-stream-search
                                                        class="vc-input !mt-0 mb-2 py-1.5 text-sm"
                                                        placeholder="Search videos by name or UID"
                                                        autocomplete="off" />
                                                    <select
                                                        name="stream_video_id"
                                                        class="vc-input !mt-0 py-1.5 text-sm"
                                                        data-stream-select>
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
                                                        Saving with a selected video enforces signed URLs and refreshes
                                                        duration.
                                                    </p>
                                                </div>

                                                <div
                                                    class="mt-1 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3 sm:col-span-6">
                                                    <div class="flex items-center gap-3">
                                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                                            <input
                                                                type="checkbox"
                                                                name="is_published"
                                                                value="1"
                                                                @checked($lesson->is_published) />
                                                            Live
                                                        </label>
                                                        <span class="text-xs text-slate-500">Auto sync</span>
                                                    </div>

                                                    <div class="flex items-center gap-2">
                                                        <button type="submit" class="vc-btn-secondary">
                                                            Save lesson
                                                        </button>
                                                        <button
                                                            type="submit"
                                                            form="delete-lesson-{{ $lesson->id }}"
                                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                            title="Delete lesson"
                                                            aria-label="Delete lesson">
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 24 24"
                                                                class="h-4 w-4 fill-current">
                                                                <path
                                                                    d="M9 3a1 1 0 0 0-1 1v1H5a1 1 0 1 0 0 2h1v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7h1a1 1 0 1 0 0-2h-3V4a1 1 0 0 0-1-1H9Zm2 2h2v1h-2V5Zm-3 2h8v12H8V7Zm2 2a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm4 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                    Summary
                                                </label>
                                                <p class="mb-2 text-xs text-slate-500">Supports basic Markdown.</p>
                                                <textarea name="summary" rows="8" class="vc-input !mt-0 text-sm">
{{ $lesson->summary }}</textarea
                                                >
                                            </div>
                                        </div>
                                    </form>
                                    <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3">
                                        <h4 class="text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                            Lesson resources (PDF)
                                        </h4>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.resources.lesson.store', $lesson) }}"
                                            enctype="multipart/form-data"
                                            data-async-curriculum
                                            data-async-success="Lesson resource uploaded."
                                            class="mt-2 grid gap-3 sm:grid-cols-6">
                                            @csrf
                                            <div class="sm:col-span-3">
                                                <input
                                                    name="name"
                                                    class="vc-input !mt-0 py-1.5 text-sm"
                                                    placeholder="Lesson worksheet.pdf" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <input
                                                    name="resource_file"
                                                    type="file"
                                                    accept="application/pdf"
                                                    class="vc-input !mt-0 py-1.5 text-sm"
                                                    required />
                                            </div>
                                            <div class="flex items-end sm:col-span-1">
                                                <button type="submit" class="vc-btn-secondary w-full justify-center">Upload</button>
                                            </div>
                                        </form>
                                        @if ($lesson->resources->isNotEmpty())
                                            <ul class="mt-2 space-y-1">
                                                @foreach ($lesson->resources as $resource)
                                                    <li
                                                        class="flex items-center justify-between rounded-md border border-slate-200 px-2.5 py-1.5 text-xs">
                                                        <span class="text-slate-700">{{ $resource->name }}</span>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('admin.resources.destroy', $resource) }}"
                                                            data-async-curriculum
                                                            data-async-success="Resource deleted.">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                                title="Delete resource"
                                                                aria-label="Delete resource">
                                                                <svg
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 24 24"
                                                                    class="h-3.5 w-3.5 fill-current">
                                                                    <path
                                                                        d="M9 3a1 1 0 0 0-1 1v1H5a1 1 0 1 0 0 2h1v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7h1a1 1 0 1 0 0-2h-3V4a1 1 0 0 0-1-1H9Zm2 2h2v1h-2V5Zm-3 2h8v12H8V7Z" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                    <form
                                        id="delete-lesson-{{ $lesson->id }}"
                                        method="POST"
                                        action="{{ route('admin.lessons.destroy', $lesson) }}"
                                        data-async-curriculum
                                        data-async-success="Lesson deleted."
                                        class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </article>
                            @empty
                                <p class="text-sm text-slate-600">No lessons in this module yet.</p>
                            @endforelse
                        </div>
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-600">No modules yet. Create the first module above.</p>
            @endforelse
        </div>
    </section>

    <section class="vc-panel mt-6 hidden p-6" data-admin-tab-panel="assets">
        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Assets</h2>
        <p class="mt-2 text-sm text-slate-600">
            Browse Stream assets available to this course and verify durations before assigning lessons.
        </p>

        @if ($streamCatalogStatus)
            <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                Cloudflare Stream list unavailable: {{ $streamCatalogStatus }}
            </div>
        @elseif (count($streamVideos) === 0)
            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                No Cloudflare Stream assets found for this account.
            </div>
        @else
            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                <label class="text-sm font-medium text-slate-700">Search assets</label>
                <input
                    type="text"
                    data-assets-search
                    class="vc-input mt-2"
                    placeholder="Filter by video name or UID"
                    autocomplete="off" />
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Video Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">UID</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($streamVideos as $video)
                            <tr data-assets-row>
                                <td class="px-4 py-3 text-slate-900">{{ $video['name'] ?: 'Untitled Video' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $video['uid'] }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $video['duration_seconds'] ? $video['duration_seconds'].'s' : 'Unknown' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div
        id="admin-curriculum-toast"
        class="pointer-events-none fixed top-4 left-1/2 z-50 hidden -translate-x-1/2 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg"></div>

    <script>
        (() => {
            const storageKey = 'admin-course-edit-tab';
            const moduleStateKey = 'admin-course-module-state:{{ $course->id }}';
            const tabButtons = () => Array.from(document.querySelectorAll('[data-admin-tab-button]'));
            const tabPanels = () => Array.from(document.querySelectorAll('[data-admin-tab-panel]'));
            const moduleCards = () => Array.from(document.querySelectorAll('[data-module-card][data-module-id]'));

            const setActiveTab = (tab) => {
                const panels = tabPanels();
                const buttons = tabButtons();
                const target = panels.some((panel) => panel.dataset.adminTabPanel === tab) ? tab : 'details';

                buttons.forEach((button) => {
                    const active = button.dataset.adminTabButton === target;
                    button.classList.toggle('bg-slate-900', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('shadow-sm', active);
                    button.classList.toggle('text-slate-700', !active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    const active = panel.dataset.adminTabPanel === target;
                    panel.classList.toggle('hidden', !active);
                });

                localStorage.setItem(storageKey, target);
            };

            const bindTabButtons = () => {
                tabButtons().forEach((button) => {
                    if (button.dataset.tabsBound === '1') {
                        return;
                    }
                    button.dataset.tabsBound = '1';
                    button.addEventListener('click', () => setActiveTab(button.dataset.adminTabButton));
                });
            };

            bindTabButtons();

            const hashTab =
                window.location.hash === '#curriculum'
                    ? 'curriculum'
                    : window.location.hash === '#assets'
                      ? 'assets'
                      : null;
            const savedTab = localStorage.getItem(storageKey);
            setActiveTab(hashTab ?? savedTab ?? 'details');

            const normalize = (value) => (value ?? '').toString().trim().toLowerCase();
            const readModuleState = () => {
                try {
                    const parsed = JSON.parse(localStorage.getItem(moduleStateKey) ?? '{}');
                    return parsed && typeof parsed === 'object' ? parsed : {};
                } catch {
                    return {};
                }
            };
            const writeModuleState = (state) => {
                localStorage.setItem(moduleStateKey, JSON.stringify(state));
            };

            const setModuleExpanded = (card, expanded, persist = true) => {
                const content = card.querySelector('[data-module-content]');
                const toggle = card.querySelector('[data-module-toggle]');
                const moduleId = card.dataset.moduleId;

                if (!content || !toggle || !moduleId) {
                    return;
                }

                content.classList.toggle('hidden', !expanded);
                toggle.textContent = expanded ? 'Collapse' : 'Expand';
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');

                if (persist) {
                    const state = readModuleState();
                    state[moduleId] = expanded;
                    writeModuleState(state);
                }
            };

            const bindModuleAccordions = () => {
                const state = readModuleState();
                moduleCards().forEach((card) => {
                    const toggle = card.querySelector('[data-module-toggle]');
                    const moduleId = card.dataset.moduleId;
                    if (!toggle || !moduleId) {
                        return;
                    }

                    if (toggle.dataset.moduleToggleBound !== '1') {
                        toggle.dataset.moduleToggleBound = '1';
                        toggle.addEventListener('click', () => {
                            const expanded = toggle.getAttribute('aria-expanded') === 'true';
                            setModuleExpanded(card, !expanded);
                        });
                    }

                    const saved = state[moduleId];
                    setModuleExpanded(card, saved === undefined ? true : Boolean(saved), false);
                });

                const expandAllButton = document.querySelector('[data-modules-expand-all]');
                if (expandAllButton && expandAllButton.dataset.modulesExpandBound !== '1') {
                    expandAllButton.dataset.modulesExpandBound = '1';
                    expandAllButton.addEventListener('click', () => {
                        moduleCards().forEach((card) => setModuleExpanded(card, true));
                    });
                }

                const collapseAllButton = document.querySelector('[data-modules-collapse-all]');
                if (collapseAllButton && collapseAllButton.dataset.modulesCollapseBound !== '1') {
                    collapseAllButton.dataset.modulesCollapseBound = '1';
                    collapseAllButton.addEventListener('click', () => {
                        moduleCards().forEach((card) => setModuleExpanded(card, false));
                    });
                }
            };

            const bindStreamSearch = (scope = document) => {
                scope.querySelectorAll('input[data-stream-search]').forEach((searchInput) => {
                    if (searchInput.dataset.streamBound === '1') {
                        return;
                    }
                    searchInput.dataset.streamBound = '1';
                    const container = searchInput.closest('div');
                    const select = container?.querySelector('select[data-stream-select]');

                    if (!select) {
                        return;
                    }

                    const filterOptions = () => {
                        const term = normalize(searchInput.value);
                        const selectedValue = select.value;

                        Array.from(select.options).forEach((option, index) => {
                            if (index === 0 || option.value === selectedValue || term === '') {
                                option.hidden = false;
                                return;
                            }

                            option.hidden = !normalize(option.textContent).includes(term);
                        });
                    };

                    searchInput.addEventListener('input', filterOptions);
                    filterOptions();
                });
            };

            const bindAssetsSearch = () => {
                const assetsSearchInput = document.querySelector('input[data-assets-search]');
                if (!assetsSearchInput || assetsSearchInput.dataset.assetsBound === '1') {
                    return;
                }
                assetsSearchInput.dataset.assetsBound = '1';
                const rows = Array.from(document.querySelectorAll('[data-assets-row]'));
                const filterAssetRows = () => {
                    const term = normalize(assetsSearchInput.value);

                    rows.forEach((row) => {
                        if (term === '') {
                            row.classList.remove('hidden');
                            return;
                        }

                        row.classList.toggle('hidden', !normalize(row.textContent).includes(term));
                    });
                };

                assetsSearchInput.addEventListener('input', filterAssetRows);
                filterAssetRows();
            };

            const bindAsyncCurriculumForms = () => {
                document.querySelectorAll('form[data-async-curriculum]').forEach((form) => {
                    if (form.dataset.asyncBound === '1') {
                        return;
                    }
                    form.dataset.asyncBound = '1';

                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const toastEl = document.getElementById('admin-curriculum-toast');
                        const submitButtons = form.querySelectorAll('button[type="submit"]');
                        submitButtons.forEach((button) => (button.disabled = true));
                        form.classList.add('opacity-70');

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                body: new FormData(form),
                                credentials: 'same-origin',
                                headers: {
                                    Accept: 'text/html',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            const html = await response.text();
                            const doc = new DOMParser().parseFromString(html, 'text/html');

                            const currentCurriculum = document.querySelector('[data-admin-tab-panel="curriculum"]');
                            const incomingCurriculum = doc.querySelector('[data-admin-tab-panel="curriculum"]');
                            if (currentCurriculum && incomingCurriculum) {
                                currentCurriculum.replaceWith(incomingCurriculum);
                            }

                            const currentSummary = document.querySelector('[data-course-count-summary]');
                            const incomingSummary = doc.querySelector('[data-course-count-summary]');
                            if (currentSummary && incomingSummary) {
                                currentSummary.replaceWith(incomingSummary);
                            }

                            bindTabButtons();
                            bindStreamSearch();
                            bindAssetsSearch();
                            bindModuleAccordions();
                            bindAsyncCurriculumForms();
                            setActiveTab('curriculum');

                            if (toastEl) {
                                toastEl.textContent = form.dataset.asyncSuccess || 'Saved.';
                                toastEl.classList.remove('hidden', 'bg-rose-600');
                                toastEl.classList.add('bg-emerald-600');
                                clearTimeout(window.__adminCurriculumToastTimeout);
                                window.__adminCurriculumToastTimeout = window.setTimeout(() => {
                                    toastEl.classList.add('hidden');
                                }, 1800);
                            }
                        } catch (error) {
                            if (toastEl) {
                                toastEl.textContent = 'Update failed. Retrying with full page submit...';
                                toastEl.classList.remove('hidden', 'bg-emerald-600');
                                toastEl.classList.add('bg-rose-600');
                                clearTimeout(window.__adminCurriculumToastTimeout);
                                window.__adminCurriculumToastTimeout = window.setTimeout(() => {
                                    toastEl.classList.add('hidden');
                                }, 2200);
                            }
                            // Keep fallback simple: if async request fails, revert to full submit.
                            form.removeAttribute('data-async-curriculum');
                            form.submit();
                        } finally {
                            form.classList.remove('opacity-70');
                            submitButtons.forEach((button) => (button.disabled = false));
                        }
                    });
                });
            };

            bindStreamSearch();
            bindAssetsSearch();
            bindModuleAccordions();
            bindAsyncCurriculumForms();
        })();
    </script>
</x-public-layout>
