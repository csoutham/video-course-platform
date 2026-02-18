<button {{ $attributes->merge(['type' => 'submit', 'class' => 'vc-btn-primary justify-center']) }}>
    {{ $slot }}
</button>
