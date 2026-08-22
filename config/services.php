<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        'image_model' => env('GEMINI_IMAGE_MODEL', 'gemini-3.1-flash-lite-image'), // Nano Banana 2 Lite
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'image_timeout' => (int) env('GEMINI_IMAGE_TIMEOUT', 45),
    ],

    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK_URL', 'https://www.facebook.com/p/Rushq-Cakes-100092729501011/'),
        'instagram' => env('SOCIAL_INSTAGRAM_URL', 'https://www.instagram.com/rushq_cakes_/'),
        'tiktok' => env('SOCIAL_TIKTOK_URL', 'https://www.tiktok.com/@rushq.cakes'),
        'whatsapp' => env('SOCIAL_WHATSAPP_URL', 'https://wa.me/94767681678'),
    ],

];
