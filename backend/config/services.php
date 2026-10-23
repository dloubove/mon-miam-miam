<?php

return [

    // Temporary, one-time administrator bootstrap; keep empty except during initial setup.
    'admin_bootstrap_token' => env('BOOTSTRAP_ADMIN_TOKEN'),

    'imagekit' => [
        'private_key' => env('IMAGEKIT_PRIVATE_KEY'),
        'url_endpoint' => env('IMAGEKIT_URL_ENDPOINT'),
    ],

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

    'mobile_money' => [
        'mode' => env('MOBILE_MONEY_MODE', 'simulation'),
        'url' => env('MOBILE_MONEY_AGGREGATOR_URL'),
        'key' => env('MOBILE_MONEY_AGGREGATOR_KEY'),
        'webhook_secret' => env('MOBILE_MONEY_WEBHOOK_SECRET'),
        'currency' => env('MOBILE_MONEY_CURRENCY', 'XAF'),
    ],

    'cinetpay' => [
        'api_key' => env('CINETPAY_API_KEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        'secret_key' => env('CINETPAY_SECRET_KEY'),
    ],

];
