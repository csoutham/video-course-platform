<x-public-layout maxWidth="max-w-none" containerPadding="px-4 py-6 lg:px-8" title="Udemy Import">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin Import</p>
                <h1 class="vc-title">Import from Udemy URL</h1>
                <p class="vc-subtitle">
                    Paste a public Udemy course URL to preview and create/update local course shells.
                </p>
            </div>
            <a href="{{ route('admin.courses.index') }}" class="vc-btn-secondary">Back to Courses</a>
        </div>
    </section>

    <section class="vc-panel mt-6 p-6">
        <form method="POST" action="{{ route('admin.imports.udemy.preview') }}" class="grid gap-4 md:grid-cols-8">
            @csrf
            <div class="md:col-span-6">
                <label for="source_url" class="vc-label">Udemy Course URL</label>
                <input
                    id="source_url"
                    name="source_url"
                    type="url"
                    required
                    value="{{ $sourceUrl }}"
                    class="vc-input"
                    placeholder="https://www.udemy.com/course/example-course/" />
                @error('source_url')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-end md:col-span-2">
                <button type="submit" class="vc-btn-primary w-full justify-center">Preview Import</button>
            </div>

            <div class="md:col-span-8">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input class="vc-checkbox" type="checkbox" name="confirm_ownership" value="1" required />
                    I confirm I have rights to migrate this source content.
                </label>
                @error('confirm_ownership')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-8">
                <label for="source_html" class="vc-label">HTML fallback (optional)</label>
                <p class="vc-help">
                    If Udemy blocks server fetch with a Cloudflare challenge, open the Udemy URL in your browser, view
                    page source, and paste it here.
                </p>
                <textarea
                    id="source_html"
                    name="source_html"
                    rows="8"
                    class="vc-input mt-2 font-mono text-xs"
                    placeholder="Paste full page source HTML here">
{{ $sourceHtml }}</textarea
                >
                @error('source_html')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>
        </form>
    </section>

    @if ($preview)
        @php
            $parsed = $preview['parsed'];
            $existingCourse = $preview['existing_course'];
        @endphp

        <section class="vc-panel mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Preview</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ $existingCourse ? 'Existing course found. Commit will update based on selected mode.' : 'No existing course found. Commit will create a new draft course.' }}
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Title</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $parsed['title'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Source External ID</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $parsed['source_external_id'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Description</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $parsed['description'] ?: 'No description found.' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Thumbnail</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $parsed['thumbnail_url'] ?: 'No image found.' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Modules</p>
                    <p class="mt-1 text-sm text-slate-900">{{ count($parsed['modules']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Lessons</p>
                    <p class="mt-1 text-sm text-slate-900">
                        {{ collect($parsed['modules'])->sum(fn ($module) => count($module['lessons'])) }}
                    </p>
                </div>
            </div>

            @if (!empty($parsed['warnings']))
                <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Warnings</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($parsed['warnings'] as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-slate-700">Imported modules and lesson shells</h3>
                @if (count($parsed['modules']) === 0)
                    <p class="mt-2 text-sm text-slate-600">
                        No sections found in source. Commit will import metadata only.
                    </p>
                @else
                    <div class="mt-2 overflow-hidden rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead
                                class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                <tr>
                                    <th class="px-3 py-2">Module</th>
                                    <th class="px-3 py-2">Lessons</th>
                                    <th class="px-3 py-2">Duration</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                @foreach ($parsed['modules'] as $module)
                                    <tr>
                                        <td class="px-3 py-2">{{ $module['name'] }}</td>
                                        <td class="px-3 py-2">
                                            <p>{{ count($module['lessons']) }}</p>
                                            @if (count($module['lessons']) > 0)
                                                <p class="vc-help">
                                                    {{ collect($module['lessons'])->pluck('name')->take(3)->implode(', ') }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ $module['duration_seconds'] ? $module['duration_seconds'].'s' : 'Unknown' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.imports.udemy.commit') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="source_url" value="{{ $parsed['source_url'] }}" />
                <textarea name="source_html" class="hidden">{{ $sourceHtml }}</textarea>

                <div>
                    <label for="overwrite_mode" class="vc-label">Overwrite mode</label>
                    <select id="overwrite_mode" name="overwrite_mode" class="vc-input">
                        <option value="safe_merge" @selected($overwriteMode === 'safe_merge')>
                            Safe merge (recommended)
                        </option>
                        <option value="force_replace_metadata" @selected($overwriteMode === 'force_replace_metadata')>
                            Force replace metadata
                        </option>
                        <option
                            value="force_replace_curriculum"
                            @selected($overwriteMode === 'force_replace_curriculum')>
                            Force replace curriculum shells
                        </option>
                        <option value="full_replace_imported" @selected($overwriteMode === 'full_replace_imported')>
                            Full replace imported data
                        </option>
                    </select>
                    @error('overwrite_mode')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input class="vc-checkbox" type="checkbox" name="confirm_ownership" value="1" required />
                        I confirm I have rights to migrate this source content.
                    </label>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="vc-btn-primary">Commit Import</button>
                    <a href="{{ route('admin.imports.udemy.show') }}" class="vc-btn-secondary">Reset</a>
                </div>
            </form>
        </section>
    @endif
</x-public-layout>
