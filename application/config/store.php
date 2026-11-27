<?php
return [
    'mail_daily_limit' => max(1,(int)env('STORE_MAIL_DAILY_LIMIT',900)),
    'payment_window_minutes' => max(5,(int)env('PAYMENT_WINDOW_MINUTES',30)),
    'demo' => (bool) env('STORE_DEMO', true),
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_hash' => env('ADMIN_PASSWORD_HASH'),
    'key_id' => env('RAZORPAY_KEY_ID'),
    'key_secret' => env('RAZORPAY_KEY_SECRET'),
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
];
