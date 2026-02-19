<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Branding">
    @if (session('status'))
        <p class="vc-alert vc-alert-success mb-6">{{ session('status') }}</p>
    @endif

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <article class="vc-panel p-6">
            <form
                method="POST"
                action="{{ route('admin.branding.update') }}"
                enctype="multipart/form-data"
                class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="platform_name" class="vc-label">Platform name</label>
                    <input
                        id="platform_name"
                        name="platform_name"
                        required
                        maxlength="120"
                        value="{{ old('platform_name', $branding->platformName) }}"
                        class="vc-input" />
                    @error('platform_name')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="logo" class="vc-label">Logo</label>
                    @if ($branding->logoUrl)
                        <img
                            src="{{ $branding->logoUrl }}"
                            alt="{{ $branding->platformName }} logo"
                            class="mt-2 h-16 w-auto rounded-lg border border-slate-200 bg-white object-contain p-2" />
                    @endif

                    <input
                        id="logo"
                        name="logo"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="vc-input" />
                    <p class="vc-help">PNG/JPG/WEBP up to 5MB. Replacing logo removes the previous file.</p>
                    @error('logo')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-semibold tracking-[0.12em] text-slate-600 uppercase">Typography</h2>

                    <div>
                        <label for="font_provider" class="vc-label">Font provider</label>
                        <select id="font_provider" name="font_provider" class="vc-input" data-font-provider>
                            @foreach ($fontProviders as $fontProvider)
                                <option
                                    value="{{ $fontProvider }}"
                                    @selected(old('font_provider', $branding->fontProvider) === $fontProvider)>
                                    {{ str($fontProvider)->title() }}
                                </option>
                            @endforeach
                        </select>
                        @error('font_provider')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="font_family" class="vc-label">Font family</label>
                        <input
                            id="font_family"
                            name="font_family"
                            class="vc-input"
                            maxlength="120"
                            value="{{ old('font_family', $branding->fontFamily) }}"
                            placeholder="e.g. Figtree, Manrope, Instrument Sans"
                            data-font-family />
                        @error('font_family')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="font_weights" class="vc-label">Font weights</label>
                        <input
                            id="font_weights"
                            name="font_weights"
                            class="vc-input"
                            maxlength="80"
                            value="{{ old('font_weights', $branding->fontWeights) }}"
                            placeholder="400,500,600,700"
                            data-font-weights />
                        <p class="vc-help">
                            Comma-separated hundreds only (e.g. 400,500,700). Used when loading Bunny/Google fonts.
                        </p>
                        @error('font_weights')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-sm font-semibold tracking-[0.12em] text-slate-600 uppercase">Core color tokens</h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($tokenColumnMap as $token => $column)
                            @php
                                $label = str($token)->replace('vc-', '')->replace('-', ' ')->title();
                                $value = old($column, $branding->colors[$token] ?? $defaults['colors'][$token] ?? '#000000');
                            @endphp

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <label for="{{ $column }}" class="vc-label">{{ $label }}</label>
                                <div class="mt-2 flex items-center gap-2">
                                    <input
                                        id="{{ $column }}"
                                        type="color"
                                        value="{{ $value }}"
                                        class="h-10 w-14 rounded-lg border border-slate-300 bg-white p-1"
                                        data-branding-color-input />
                                    <input
                                        name="{{ $column }}"
                                        value="{{ $value }}"
                                        class="vc-input mt-0"
                                        pattern="^#([A-Fa-f0-9]{6})$"
                                        maxlength="7"
                                        data-branding-color-text />
                                </div>
                                @error($column)
                                    <p class="vc-error">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="vc-btn-primary">Save branding</button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.branding.reset') }}" class="mt-3">
                @csrf
                <button type="submit" class="vc-btn-danger" onclick="return confirm('Reset branding to defaults?');">
                    Reset defaults
                </button>
            </form>
        </article>

        <aside class="vc-panel p-6">
            <p class="vc-eyebrow">Preview</p>
            <h2 class="vc-card-title mt-2" data-branding-preview-name>
                {{ old('platform_name', $branding->platformName) }}
            </h2>
            <div class="vc-panel mt-4 p-4" data-branding-preview-surface>
                <p class="text-sm" data-branding-preview-copy>
                    This preview uses your selected runtime branding colors without requiring an asset rebuild.
                </p>
                <p class="mt-2 text-sm font-medium" data-branding-preview-font>
                    The quick brown fox jumps over the lazy dog.
                </p>
                <div class="mt-4 flex gap-2">
                    <button type="button" class="vc-btn-primary" data-branding-preview-primary>Primary</button>
                    <button type="button" class="vc-btn-secondary" data-branding-preview-secondary>Secondary</button>
                </div>
            </div>
            <p class="vc-help mt-4">Live preview updates as you change fields. Final values are validated on save.</p>
        </aside>
    </section>

    <script>
        (() => {
            const colorTextInputs = Array.from(document.querySelectorAll('[data-branding-color-text]'));
            const colorPickers = Array.from(document.querySelectorAll('[data-branding-color-input]'));
            const nameInput = document.getElementById('platform_name');
            const previewName = document.querySelector('[data-branding-preview-name]');
            const fontProviderInput = document.querySelector('[data-font-provider]');
            const fontFamilyInput = document.querySelector('[data-font-family]');
            const fontWeightsInput = document.querySelector('[data-font-weights]');
            let dynamicFontLink = null;

            const syncPreview = () => {
                colorTextInputs.forEach((input) => {
                    if (!input.name || !/^#([A-Fa-f0-9]{6})$/.test(input.value)) return;

                    const token = input.name.replace(/^color_/, 'vc-').replaceAll('_', '-');

                    document.documentElement.style.setProperty(`--${token}`, input.value.toUpperCase());
                });

                if (previewName && nameInput) {
                    previewName.textContent = nameInput.value || '{{ $defaults['platform_name'] }}';
                }

                const provider = fontProviderInput?.value || 'bunny';
                const family = (fontFamilyInput?.value || '{{ $defaults['font_family'] }}').trim();
                const cssFamily = provider === 'system'
                    ? 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif'
                    : `"${family.replaceAll('"', '')}", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif`;

                document.documentElement.style.setProperty('--vc-font-sans', cssFamily);

                const validWeights = (fontWeightsInput?.value || '{{ $defaults['font_weights'] }}')
                    .split(',')
                    .map((value) => value.trim())
                    .filter((value) => /^[1-9]00$/.test(value))
                    .join(provider === 'google' ? ';' : ',');

                const familyParam = family.replaceAll(' ', '+');
                const href = provider === 'google'
                    ? `https://fonts.googleapis.com/css2?family=${familyParam}:wght@${validWeights}&display=swap`
                    : `https://fonts.bunny.net/css?family=${familyParam}:${validWeights}&display=swap`;

                if (dynamicFontLink) {
                    dynamicFontLink.remove();
                    dynamicFontLink = null;
                }

                if (provider !== 'system' && familyParam !== '' && validWeights !== '') {
                    dynamicFontLink = document.createElement('link');
                    dynamicFontLink.rel = 'stylesheet';
                    dynamicFontLink.href = href;
                    document.head.appendChild(dynamicFontLink);
                }
            };

            colorPickers.forEach((picker, index) => {
                picker.addEventListener('input', () => {
                    const textInput = colorTextInputs[index];
                    if (!textInput) return;
                    textInput.value = picker.value.toUpperCase();
                    syncPreview();
                });
            });

            colorTextInputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    const picker = colorPickers[index];
                    if (picker && /^#([A-Fa-f0-9]{6})$/.test(input.value)) {
                        picker.value = input.value;
                    }
                    syncPreview();
                });
            });

            nameInput?.addEventListener('input', syncPreview);
            fontProviderInput?.addEventListener('change', syncPreview);
            fontFamilyInput?.addEventListener('input', syncPreview);
            fontWeightsInput?.addEventListener('input', syncPreview);
            syncPreview();
        })();
    </script>
</x-public-layout>
