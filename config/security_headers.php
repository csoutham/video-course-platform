<?php

return [
    'enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),
    'hsts_enabled' => (bool) env('SECURITY_HSTS_ENABLED', true),
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
];

