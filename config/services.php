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

    // Org-standard external authentication (see authentication-implementation-guide.md)
    'auth_api' => [
        'base_uri' => env('AUTH_API_BASE_URI', ''),
        'api_key' => env('AUTH_API_KEY', ''),
        'auth_user_api_key' => env('AUTH_USER_API_KEY', ''),
        // Dev-only: skip the external API entirely and accept any password for a
        // local users.email. Ignored in production no matter what .env says.
        'fake' => env('AUTH_FAKE', false),
    ],

    'user_api' => [
        'endpoint' => env('USER_API_ENDPOINT', ''),
        'key' => env('USER_API_KEY', ''),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY', ''),
        'secret' => env('TURNSTILE_SECRET_KEY', ''),
        'verify' => env('TURNSTILE_VERIFY', false),
    ],

    // Dev-only v1/v2 live comparison tool (project-overview/legacy-peek-tool-plan.md).
    // Blank base_uri disables it — see App\Services\LegacyPeekService.
    'legacy_v1' => [
        'base_uri' => env('LEGACY_V1_BASE_URI', ''),
        'key' => env('LEGACY_V1_API_KEY', ''),
    ],

];
