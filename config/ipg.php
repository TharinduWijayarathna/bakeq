<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Online payment gateway
    |--------------------------------------------------------------------------
    |
    | Secrets and toggles for the hosted checkout IPG. Keys stay server-side;
    | the storefront only ever sees "online payment" copy.
    |
    | When webhooks are disabled (default), payment confirmation happens on the
    | customer success return URL by retrieving the checkout session from the
    | provider. Enable webhooks when you have a public endpoint and secret.
    |
    */

    'enabled' => (bool) env('IPG_ENABLED', true),

    'secret_key' => env('IPG_SECRET_KEY'),

    'publishable_key' => env('IPG_PUBLISHABLE_KEY'),

    'webhooks_enabled' => (bool) env('IPG_WEBHOOKS_ENABLED', false),

    'webhook_secret' => env('IPG_WEBHOOK_SECRET'),

    'currency' => strtolower((string) env('IPG_CURRENCY', 'lkr')),

];
