<?php

return [

    // Shown on receipts and in the app header.
    'name' => env('APP_NAME', 'Neat Handwriting'),

    'currency' => env('APP_CURRENCY', '₹'),

    // Added to 10-digit local numbers when building WhatsApp links.
    'phone_country_code' => env('PHONE_COUNTRY_CODE', '91'),

    // Account created by DatabaseSeeder on first install.
    'owner' => [
        'name' => env('OWNER_NAME', 'Teacher'),
        'email' => env('OWNER_EMAIL', 'teacher@example.com'),
        'password' => env('OWNER_PASSWORD'),
    ],

];
