@props(['value'])

<label {{ $attributes->merge(['class' => 'vc-label']) }}>
    {{ $value ?? $slot }}
</label>
