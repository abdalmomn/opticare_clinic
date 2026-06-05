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
    'recaptcha' => [
        'site_key'        => env('RECAPTCHA_SITE_KEY'),
        'secret_key'      => env('RECAPTCHA_SECRET_KEY'),
        'min_score'       => env('RECAPTCHA_MIN_SCORE', 0.5),
        'expected_action' => env('RECAPTCHA_EXPECTED_ACTION'),
    ],
    'traccar_sms' => [
        'enabled'              => env('TRACCAR_SMS_ENABLED', false),
        'url'                  => env('TRACCAR_SMS_URL'),
        'token'                => env('TRACCAR_SMS_TOKEN'),
        'default_country_code' => env('TRACCAR_SMS_DEFAULT_COUNTRY_CODE', '963'),
    ],
];
