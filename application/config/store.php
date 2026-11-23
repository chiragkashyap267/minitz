<?php
return [
    'demo' => (bool) env('STORE_DEMO', true),
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_hash' => env('ADMIN_PASSWORD_HASH'),
    'key_id' => env('RAZORPAY_KEY_ID'),
    'key_secret' => env('RAZORPAY_KEY_SECRET'),
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
];
