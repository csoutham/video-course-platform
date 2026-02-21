<x-public-layout>
    <x-slot:title>Certificate Verification</x-slot>

    <div class="mx-auto max-w-2xl">
        <article class="vc-panel space-y-4 p-6">
            <p class="vc-eyebrow">Verification</p>
            <h1 class="vc-title">Certificate {{ $certificate->status === 'active' ? 'Verified' : 'Revoked' }}</h1>

            @if ($certificate->status === 'active')
                <div class="vc-alert vc-alert-success">This certificate is valid.</div>
            @else
                <div class="vc-alert vc-alert-warning">This certificate has been revoked.</div>
            @endif

            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-semibold text-slate-700">Verification code</dt>
                    <dd class="text-slate-600">{{ $certificate->verification_code }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-700">Issued to</dt>
                    <dd class="text-slate-600">{{ $certificate->issued_name }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-700">Course</dt>
                    <dd class="text-slate-600">{{ $certificate->issued_course_title }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-700">Issued date</dt>
                    <dd class="text-slate-600">{{ $certificate->issued_at?->format('j M Y') ?? 'N/A' }}</dd>
                </div>
                @if ($certificate->status === 'revoked')
                    <div>
                        <dt class="font-semibold text-slate-700">Revoked date</dt>
                        <dd class="text-slate-600">{{ $certificate->revoked_at?->format('j M Y') ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-700">Reason</dt>
                        <dd class="text-slate-600">{{ $certificate->revoke_reason ?? 'N/A' }}</dd>
                    </div>
                @endif
            </dl>
        </article>
    </div>
</x-public-layout>
