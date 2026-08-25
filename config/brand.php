<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bakery brand identity
    |--------------------------------------------------------------------------
    |
    | Customer-facing names and copy. Prefer these over hardcoding "Bakeq" or
    | "Rushq" in views so storefront, admin, and PDFs stay consistent.
    |
    */

    'name' => env('BRAND_NAME', 'Rushq cakes by Shashi'),

    'short_name' => env('BRAND_SHORT_NAME', 'Rushq cakes'),

    'tagline' => env('BRAND_TAGLINE', 'Baked with love, made for you'),

    'admin_label' => env('BRAND_ADMIN_LABEL', 'Rushq Admin'),

];
