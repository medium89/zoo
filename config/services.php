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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET'),
    ],

    'yandex' => [
        'maps_api_key' => env('YANDEX_MAPS_API_KEY'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_ids' => array_filter([
            env('TELEGRAM_CHAT_ID'),
            env('TELEGRAM_CHAT_ID_2'),
        ]),
        'allowed_user_ids' => array_filter(array_map('trim', explode(',', env('TELEGRAM_ALLOWED_USER_IDS', '')))),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    'aitunnel' => [
        'base_url' => env('AITUNNEL_BASE_URL', 'https://api.aitunnel.ru/v1'),
        'api_key' => env('AITUNNEL_API_KEY'),
        'chat_model' => env('AITUNNEL_CHAT_MODEL', 'gemini-2.5-flash-lite'),
        'stt_model' => env('AITUNNEL_STT_MODEL', 'whisper-1'),
    ],

];
