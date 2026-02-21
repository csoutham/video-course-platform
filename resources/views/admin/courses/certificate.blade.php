<x-admin-layout maxWidth="max-w-none" containerPadding="px-4 py-6" title="Course Certificate">
    <section class="vc-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="vc-heading-block">
                <p class="vc-eyebrow">Admin</p>
                <h1 class="vc-title">Certificate Settings</h1>
                <p class="vc-subtitle">Configure completion certificate generation for {{ $course->title }}.</p>
            </div>
            <a href="{{ route('admin.courses.edit', $course) }}" class="vc-btn-secondary">Back to Course</a>
        </div>
    </section>

    <section class="vc-panel mt-6 p-6">
        <form
            method="POST"
            action="{{ route('admin.courses.certificate.update', $course) }}"
            enctype="multipart/form-data"
            class="space-y-5">
            @csrf
            @method('PUT')

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input
                    class="vc-checkbox"
                    type="checkbox"
                    name="certificate_enabled"
                    value="1"
                    @checked(old('certificate_enabled', $course->certificate_enabled)) />
                Enable certificates for this course
            </label>

            <div>
                <label for="certificate_template_pdf" class="vc-label">Template PDF</label>
                <p class="vc-help">Upload a single-page PDF template. Text is overlaid in the center safe area.</p>

                @if ($course->certificate_template_path)
                    <p class="mt-2 text-xs text-slate-600">
                        Current template:
                        <code>{{ $course->certificate_template_path }}</code>
                    </p>
                @endif

                <input
                    id="certificate_template_pdf"
                    name="certificate_template_pdf"
                    type="file"
                    accept="application/pdf"
                    class="vc-input mt-2" />
                @error('certificate_template_pdf')
                    <p class="vc-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="certificate_signatory_name" class="vc-label">Signatory name (optional)</label>
                    <input
                        id="certificate_signatory_name"
                        name="certificate_signatory_name"
                        value="{{ old('certificate_signatory_name', $course->certificate_signatory_name) }}"
                        class="vc-input"
                        maxlength="120" />
                    @error('certificate_signatory_name')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="certificate_signatory_title" class="vc-label">Signatory title (optional)</label>
                    <input
                        id="certificate_signatory_title"
                        name="certificate_signatory_title"
                        value="{{ old('certificate_signatory_title', $course->certificate_signatory_title) }}"
                        class="vc-input"
                        maxlength="120" />
                    @error('certificate_signatory_title')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button class="vc-btn-primary" type="submit">Save Certificate Settings</button>
                <a href="{{ route('admin.courses.edit', $course) }}" class="vc-btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
</x-admin-layout>
