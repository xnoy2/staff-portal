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

    'cloudflare' => [
        'account_id'   => env('CLOUDFLARE_ACCOUNT_ID'),
        'stream_token' => env('CLOUDFLARE_STREAM_TOKEN'),
    ],

    'bgr' => [
        'base_url' => env('BGR_API_URL', 'https://portal.bespokegardenroomsballycastle.co.uk'),
    ],

    'bcf' => [
        'base_url' => env('BCF_API_URL', 'https://zafhtfftioqdpjwvwtra.supabase.co/functions/v1/worker-api'),
        'api_key'  => env('BCF_API_KEY', 'bcf_d5674ecb733a7364e059d31eb650f00651bb5a085e6bf8531744bac930535b12'),
    ],

    'payroll' => [
        'recipient_email' => env('PAYROLL_RECIPIENT_EMAIL', 'ofaminjumerpaul07@gmail.com'),
    ],

];
