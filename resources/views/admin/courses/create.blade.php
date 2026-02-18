<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Create Course">
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
        <form
            method="POST"
            action="{{ route('admin.courses.store') }}"
            enctype="multipart/form-data"
            class="space-y-5">
            @csrf

            <div>
                <label for="title" class="vc-label">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required class="vc-input" />
                @error('title')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="vc-label">Slug (optional)</label>
                <input id="slug" name="slug" value="{{ old('slug') }}" class="vc-input" />
                @error('slug')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="vc-label">Subtitle</label>
                <textarea id="description" name="description" rows="4" class="vc-input">
{{ old('description') }}
                </textarea>
                @error('description')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="long_description" class="vc-label">Long description (Markdown)</label>
                <textarea id="long_description" name="long_description" rows="8" class="vc-input">
{{ old('long_description') }}</textarea
                >
                @error('long_description')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="requirements" class="vc-label">Requirements (Markdown)</label>
                <textarea id="requirements" name="requirements" rows="6" class="vc-input">
{{ old('requirements') }}</textarea
                >
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
                <input id="thumbnail_image" name="thumbnail_image" type="file" accept="image/*" class="sr-only" />
                @error('thumbnail_image')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="intro_video_id" class="vc-label">Intro video (Cloudflare Stream)</label>
                @if (!empty($streamVideos))
                    <select id="intro_video_id" name="intro_video_id" class="vc-input">
                        <option value="">No intro video</option>
                        @foreach ($streamVideos as $video)
                            <option value="{{ $video['uid'] }}" @selected(old('intro_video_id') === $video['uid'])>
                                {{ $video['name'] }} ({{ $video['uid'] }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input
                        id="intro_video_id"
                        name="intro_video_id"
                        value="{{ old('intro_video_id') }}"
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
                    class="vc-input"
                    placeholder="e.g. 1234567" />
                <p class="vc-help">Purchasers of this course will be tagged in Kit when this is set.</p>
                @error('kit_tag_id')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
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
                        class="vc-input" />
                    @error('price_amount')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="price_currency" class="vc-label">Currency</label>
                    <select id="price_currency" name="price_currency" required class="vc-input">
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
                        <input class="vc-checkbox" type="checkbox" name="is_free" value="1" @checked(old('is_free')) />
                        Free course (lead magnet)
                    </label>
                    <p class="text-xs text-slate-500">
                        Free courses bypass Stripe checkout and can issue claim links directly.
                    </p>
                </div>
                <div>
                    <label for="free_access_mode" class="vc-label">Free access mode</label>
                    <select id="free_access_mode" name="free_access_mode" class="vc-input">
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

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        class="vc-checkbox"
                        type="checkbox"
                        name="is_published"
                        value="1"
                        @checked(old('is_published')) />
                    Publish course now
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        class="vc-checkbox"
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
