<?php

return [
    'video_autocomplete_percent' => (int) env('LEARNING_VIDEO_AUTOCOMPLETE_PERCENT', 90),
    'video_heartbeat_seconds' => (int) env('LEARNING_VIDEO_HEARTBEAT_SECONDS', 15),
    'gifts_enabled' => (bool) env('GIFTS_ENABLED', false),
    'subscriptions_enabled' => (bool) env('SUBSCRIPTIONS_ENABLED', false),
    'preorders_enabled' => (bool) env('PREORDERS_ENABLED', false),
];
