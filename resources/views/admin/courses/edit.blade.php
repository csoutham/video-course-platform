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

    <section class="vc-panel sticky top-2 z-20 mt-6 border border-slate-200/90 bg-white/90 p-3 backdrop-blur">
        <div class="flex flex-wrap items-center justify-between gap-3">
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

            <p class="text-xs text-slate-600">{{ $moduleCount }} modules · {{ $lessonCount }} lessons</p>
        </div>
    </section>

    <section class="vc-panel mt-6 p-6" data-admin-tab-panel="details">
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
{{ old('description', $course->description) }}
                </textarea>
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

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="price_amount" class="text-sm font-medium text-slate-700">Price (cents/pence)</label>
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

    <section class="vc-panel mt-6 hidden p-6" data-admin-tab-panel="curriculum">
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
                            <input
                                type="text"
                                data-stream-search
                                class="vc-input mb-2"
                                placeholder="Search videos by name or UID"
                                autocomplete="off" />
                            <select name="stream_video_id" class="vc-input" data-stream-select>
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
                                    <input
                                        type="text"
                                        data-stream-search
                                        class="vc-input mb-2"
                                        placeholder="Search videos by name or UID"
                                        autocomplete="off" />
                                    <select name="stream_video_id" class="vc-input" data-stream-select>
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

    <script>
        (() => {
            const storageKey = 'admin-course-edit-tab';
            const tabButtons = Array.from(document.querySelectorAll('[data-admin-tab-button]'));
            const tabPanels = Array.from(document.querySelectorAll('[data-admin-tab-panel]'));

            const setActiveTab = (tab) => {
                const target = tabPanels.some((panel) => panel.dataset.adminTabPanel === tab) ? tab : 'details';

                tabButtons.forEach((button) => {
                    const active = button.dataset.adminTabButton === target;
                    button.classList.toggle('bg-slate-900', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('shadow-sm', active);
                    button.classList.toggle('text-slate-700', !active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                tabPanels.forEach((panel) => {
                    const active = panel.dataset.adminTabPanel === target;
                    panel.classList.toggle('hidden', !active);
                });

                localStorage.setItem(storageKey, target);
            };

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => setActiveTab(button.dataset.adminTabButton));
            });

            const hashTab =
                window.location.hash === '#curriculum'
                    ? 'curriculum'
                    : window.location.hash === '#assets'
                      ? 'assets'
                      : null;
            const savedTab = localStorage.getItem(storageKey);
            setActiveTab(hashTab ?? savedTab ?? 'details');

            const normalize = (value) => (value ?? '').toString().trim().toLowerCase();

            document.querySelectorAll('input[data-stream-search]').forEach((searchInput) => {
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

            const assetsSearchInput = document.querySelector('input[data-assets-search]');
            if (assetsSearchInput) {
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
            }
        })();
    </script>
</x-public-layout>
