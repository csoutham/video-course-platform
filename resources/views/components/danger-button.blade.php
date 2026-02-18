<button {{ $attributes->merge(['type' => 'submit', 'class' => 'vc-btn-danger justify-center']) }}>
    {{ $slot }}
</button>
