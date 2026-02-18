@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'vc-alert vc-alert-success']) }}>
        {{ $status }}
    </div>
@endif
