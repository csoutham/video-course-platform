<x-admin-layout maxWidth="max-w-none" containerPadding="px-4 py-6" title="Create Course">
    @php
        $hasSeoErrors = $errors->hasAny(['seo_title', 'seo_description', 'seo_image_url', 'seo_image']);
        $hasPricingErrors = $errors->hasAny([
            'price_amount',
            'price_currency',
            'is_free',
            'free_access_mode',
            'is_subscription_excluded',
            'is_preorder_enabled',
            'preorder_starts_at',
            'preorder_ends_at',
            'release_at',
            'preorder_price_amount',
            'auto_create_stripe_price',
            'auto_create_preorder_stripe_price',
            'is_published',
        ]);
    @endphp

    <section
        class="fixed top-[55px] right-0 left-0 z-40 border-y border-slate-200/90 bg-white/95 px-4 py-3 shadow-sm backdrop-blur lg:left-72 lg:px-8">
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
                    data-admin-tab-button="pricing"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    Pricing
                </button>
                <button
                    type="button"
                    data-admin-tab-button="seo"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    SEO
                </button>
            </div>

            <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Back to Courses</a>
        </div>
    </section>

    <div class="h-16" aria-hidden="true"></div>

    <form
        id="course-create-form"
        method="POST"
        action="{{ route('admin.courses.store') }}"
        enctype="multipart/form-data"
        class="hidden">
        @csrf
    </form>

    <section class="vc-panel p-6" data-admin-tab-panel="details">
        <div class="space-y-5">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Create Course</h1>
                <p class="vc-subtitle">Create a course and provision a Stripe one-time price automatically.</p>
            </div>

            <div>
                <label for="title" class="vc-label">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required form="course-create-form" class="vc-input" />
                @error('title')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="vc-label">Slug (optional)</label>
                <input id="slug" name="slug" value="{{ old('slug') }}" form="course-create-form" class="vc-input" />
                @error('slug')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="vc-label">Subtitle</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    form="course-create-form"
                    class="vc-input">{{ old('description') }}</textarea>
                @error('description')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="long_description" class="vc-label">Long description (Markdown)</label>
                <textarea
                    id="long_description"
                    name="long_description"
                    rows="8"
                    form="course-create-form"
                    class="vc-input">{{ old('long_description') }}</textarea>
                @error('long_description')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="requirements" class="vc-label">Requirements (Markdown)</label>
                <textarea
                    id="requirements"
                    name="requirements"
                    rows="6"
                    form="course-create-form"
                    class="vc-input">{{ old('requirements') }}</textarea>
                @error('requirements')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="thumbnail_image" class="vc-label">Thumbnail image</label>
                <label
                    for="thumbnail_image"
                    class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-slate-400 hover:bg-slate-100">
                    <span class="text-sm font-semibold text-slate-700">Drop image here or click to upload</span>
                    <span class="vc-help">JPG, PNG, WEBP up to 5MB</span>
                </label>
                <input
                    id="thumbnail_image"
                    name="thumbnail_image"
                    type="file"
                    accept="image/*"
                    class="sr-only"
                    form="course-create-form" />
                @error('thumbnail_image')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="intro_video_id" class="vc-label">Intro video (Cloudflare Stream)</label>
                @if (!empty($streamVideos))
                    <input
                        type="text"
                        data-stream-search
                        class="vc-input mb-2"
                        placeholder="Search videos by title"
                        autocomplete="off" />
                    <select id="intro_video_id" name="intro_video_id" form="course-create-form" class="vc-input" data-stream-select>
                        <option value="">No intro video</option>
                        @foreach ($streamVideos as $video)
                            <option
                                value="{{ $video['uid'] }}"
                                data-video-title="{{ $video['name'] }}"
                                @selected(old('intro_video_id') === $video['uid'])>
                                {{ $video['name'] }} ({{ $video['uid'] }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input
                        id="intro_video_id"
                        name="intro_video_id"
                        value="{{ old('intro_video_id') }}"
                        form="course-create-form"
                        class="vc-input"
                        placeholder="Cloudflare Stream UID" />
                @endif
                <p class="vc-help">This intro video is shown on the public course sales page.</p>
                @if (!empty($streamCatalogStatus))
                    <p class="mt-1 text-xs text-amber-700">Stream catalog unavailable: {{ $streamCatalogStatus }}</p>
                @endif

                @error('intro_video_id')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="stream_video_filter_term" class="vc-label">Stream catalog filter term</label>
                <input
                    id="stream_video_filter_term"
                    name="stream_video_filter_term"
                    value="{{ old('stream_video_filter_term') }}"
                    form="course-create-form"
                    class="vc-input"
                    placeholder="e.g. Monologue Course" />
                <p class="vc-help">Used on the edit screen to filter Cloudflare Stream videos by name.</p>
                @error('stream_video_filter_term')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="kit_tag_id" class="vc-label">Kit tag ID (optional)</label>
                <input
                    id="kit_tag_id"
                    name="kit_tag_id"
                    type="number"
                    min="1"
                    step="1"
                    value="{{ old('kit_tag_id') }}"
                    form="course-create-form"
                    class="vc-input"
                    placeholder="e.g. 1234567" />
                <p class="vc-help">Purchasers of this course will be tagged in Kit when this is set.</p>
                @error('kit_tag_id')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button class="vc-btn-primary" type="submit" form="course-create-form">Create Course</button>
                <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Cancel</a>
            </div>
        </div>
    </section>

    <section class="vc-panel hidden p-6" data-admin-tab-panel="pricing">
        <div class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">Pricing and Access</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Configure one-time pricing, free-access mode, and preorder behavior before publishing.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="price_amount" class="vc-label">Price (cents/pence)</label>
                    <input
                        id="price_amount"
                        name="price_amount"
                        type="number"
                        min="0"
                        required
                        value="{{ old('price_amount', 9900) }}"
                        form="course-create-form"
                        class="vc-input" />
                    @error('price_amount')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="price_currency" class="vc-label">Currency</label>
                    <select id="price_currency" name="price_currency" required form="course-create-form" class="vc-input">
                        <option value="usd" @selected(old('price_currency', 'usd') === 'usd')>USD</option>
                        <option value="gbp" @selected(old('price_currency', 'usd') === 'gbp')>GBP</option>
                    </select>
                    @error('price_currency')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input class="vc-checkbox" type="checkbox" name="is_free" value="1" form="course-create-form" @checked(old('is_free')) />
                        Free course (lead magnet)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            class="vc-checkbox"
                            type="checkbox"
                            name="is_subscription_excluded"
                            value="1"
                            form="course-create-form"
                            @checked(old('is_subscription_excluded')) />
                        Exclude from subscription access
                    </label>
                    <p class="text-xs text-slate-500">
                        Free courses bypass Stripe checkout and can issue claim links directly.
                    </p>
                </div>
                <div>
                    <label for="free_access_mode" class="vc-label">Free access mode</label>
                    <select id="free_access_mode" name="free_access_mode" form="course-create-form" class="vc-input">
                        <option value="claim_link" @selected(old('free_access_mode', 'claim_link') === 'claim_link')>
                            Claim link
                        </option>
                        <option value="direct" @selected(old('free_access_mode') === 'direct')>Direct grant</option>
                    </select>
                    @error('free_access_mode')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="vc-panel-soft space-y-4 p-4">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        class="vc-checkbox"
                        type="checkbox"
                        name="is_preorder_enabled"
                        value="1"
                        form="course-create-form"
                        @checked(old('is_preorder_enabled')) />
                    Enable preorder mode
                </label>
                @error('is_preorder_enabled')
                    <p class="vc-error">{{ $message }}</p>
                @enderror

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="preorder_starts_at" class="vc-label">Preorder starts</label>
                        <input
                            id="preorder_starts_at"
                            name="preorder_starts_at"
                            type="datetime-local"
                            value="{{ old('preorder_starts_at') }}"
                            form="course-create-form"
                            class="vc-input" />
                        @error('preorder_starts_at')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="preorder_ends_at" class="vc-label">Preorder ends</label>
                        <input
                            id="preorder_ends_at"
                            name="preorder_ends_at"
                            type="datetime-local"
                            value="{{ old('preorder_ends_at') }}"
                            form="course-create-form"
                            class="vc-input" />
                        @error('preorder_ends_at')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="release_at" class="vc-label">Release date</label>
                        <input
                            id="release_at"
                            name="release_at"
                            type="datetime-local"
                            value="{{ old('release_at') }}"
                            form="course-create-form"
                            class="vc-input" />
                        @error('release_at')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="preorder_price_amount" class="vc-label">Preorder price (cents/pence)</label>
                        <input
                            id="preorder_price_amount"
                            name="preorder_price_amount"
                            type="number"
                            min="0"
                            value="{{ old('preorder_price_amount') }}"
                            form="course-create-form"
                            class="vc-input" />
                        @error('preorder_price_amount')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input
                                class="vc-checkbox"
                                type="checkbox"
                                name="auto_create_preorder_stripe_price"
                                value="1"
                                form="course-create-form"
                                @checked(old('auto_create_preorder_stripe_price', '1')) />
                            Auto-create preorder Stripe price
                        </label>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        class="vc-checkbox"
                        type="checkbox"
                        name="is_published"
                        value="1"
                        form="course-create-form"
                        @checked(old('is_published')) />
                    Publish course now
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        class="vc-checkbox"
                        type="checkbox"
                        name="auto_create_stripe_price"
                        value="1"
                        form="course-create-form"
                        @checked(old('auto_create_stripe_price', '1')) />
                    Auto-create Stripe price
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button class="vc-btn-primary" type="submit" form="course-create-form">Create Course</button>
                <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Cancel</a>
            </div>
        </div>
    </section>

    <section class="vc-panel hidden p-6" data-admin-tab-panel="seo">
        <div class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">SEO Settings</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Configure search title, description, and social preview image for this course page.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="seo_title" class="vc-label">SEO title override (optional)</label>
                    <input
                        id="seo_title"
                        name="seo_title"
                        value="{{ old('seo_title') }}"
                        form="course-create-form"
                        class="vc-input"
                        maxlength="160" />
                    <p class="vc-help">Recommended max 60-70 characters.</p>
                    @error('seo_title')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="seo_image_url" class="vc-label">SEO social image URL (optional)</label>
                    <input
                        id="seo_image_url"
                        name="seo_image_url"
                        value="{{ old('seo_image_url') }}"
                        form="course-create-form"
                        class="vc-input"
                        placeholder="https://..." />
                    @error('seo_image_url')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="seo_image" class="vc-label">SEO social image upload (optional)</label>
                <label
                    for="seo_image"
                    class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-slate-400 hover:bg-slate-100">
                    <span class="text-sm font-semibold text-slate-700">Drop image here or click to upload</span>
                    <span class="vc-help">JPG, PNG, WEBP up to 5MB. Takes priority over URL.</span>
                </label>
                <input id="seo_image" name="seo_image" type="file" accept="image/*" class="sr-only" form="course-create-form" />
                @error('seo_image')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="seo_description" class="vc-label">SEO description override (optional)</label>
                <textarea
                    id="seo_description"
                    name="seo_description"
                    rows="3"
                    form="course-create-form"
                    class="vc-input"
                    maxlength="320">{{ old('seo_description') }}</textarea>
                <p class="vc-help">Recommended max 150-160 characters.</p>
                @error('seo_description')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button class="vc-btn-primary" type="submit" form="course-create-form">Create Course</button>
                <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Cancel</a>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const storageKey = 'admin-course-create-tab';
            const tabButtons = () => Array.from(document.querySelectorAll('[data-admin-tab-button]'));
            const tabPanels = () => Array.from(document.querySelectorAll('[data-admin-tab-panel]'));

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

            tabButtons().forEach((button) => {
                button.addEventListener('click', () => setActiveTab(button.dataset.adminTabButton));
            });

            const hashToTab = {
                '#pricing': 'pricing',
                '#seo': 'seo',
            };
            const hashTab = hashToTab[window.location.hash] ?? null;
            const hasSeoErrors = @json($hasSeoErrors);
            const hasPricingErrors = @json($hasPricingErrors);
            const savedTab = localStorage.getItem(storageKey);
            setActiveTab(
                hashTab ?? (hasSeoErrors ? 'seo' : null) ?? (hasPricingErrors ? 'pricing' : null) ?? savedTab ?? 'details'
            );

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

                        option.hidden = !normalize(option.dataset.videoTitle).includes(term);
                    });
                };

                searchInput.addEventListener('input', filterOptions);
                filterOptions();
            });
        })();
    </script>
</x-admin-layout>
