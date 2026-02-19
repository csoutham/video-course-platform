<?php

return [
    'enabled' => (bool) env('BRANDING_ENABLED', true),

    'cache_key' => env('BRANDING_CACHE_KEY', 'branding:current'),

    'cache_ttl_seconds' => (int) env('BRANDING_CACHE_TTL_SECONDS', 3600),

    'disk' => env('BRANDING_DISK', 'public'),

    'defaults' => [
        'platform_name' => env('BRANDING_DEFAULT_PLATFORM_NAME', env('APP_NAME', 'VideoCourses')),
        'logo_url' => null,
        'font_provider' => env('BRANDING_DEFAULT_FONT_PROVIDER', 'bunny'),
        'font_family' => env('BRANDING_DEFAULT_FONT_FAMILY', 'Figtree'),
        'font_weights' => env('BRANDING_DEFAULT_FONT_WEIGHTS', '400,500,600,700'),
        'colors' => [
            'vc-bg' => '#F4F5F8',
            'vc-panel' => '#FFFFFF',
            'vc-panel-soft' => '#F8F9FC',
            'vc-border' => '#D7DBE4',
            'vc-text' => '#0F172A',
            'vc-muted' => '#5B6578',
            'vc-brand' => '#1E293B',
            'vc-brand-strong' => '#0B1220',
            'vc-accent' => '#0F766E',
            'vc-warning' => '#B45309',
        ],
    ],
];
