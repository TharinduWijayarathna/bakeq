<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    |
    | Secret and publishable keys for Stripe Checkout. When webhooks are
    | disabled (default), payment confirmation uses the success return URL
    | by retrieving the Checkout Session. Enable webhooks when you have a
    | public endpoint and STRIPE_WEBHOOK_SECRET.
    |
    */

    'enabled' => (bool) env('STRIPE_ENABLED', true),

    'secret_key' => env('STRIPE_SECRET_KEY'),

    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),

    'webhooks_enabled' => (bool) env('STRIPE_WEBHOOKS_ENABLED', false),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'currency' => strtolower((string) env('STRIPE_CURRENCY', 'lkr')),

];
