<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'cloudflare_stream' => [
        'iframe_base_url' => env('CF_STREAM_IFRAME_BASE_URL', 'https://iframe.videodelivery.net'),
        'signed_urls_enabled' => (bool) env('CF_STREAM_SIGNED_URLS_ENABLED', false),
        'token_ttl_seconds' => (int) env('CF_STREAM_TOKEN_TTL_SECONDS', 3600),
        'account_id' => env('CF_STREAM_ACCOUNT_ID'),
        'api_token' => env('CF_STREAM_API_TOKEN'),
        'customer_code' => env('CF_STREAM_CUSTOMER_CODE'),
    ],

];
