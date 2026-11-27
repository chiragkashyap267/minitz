<?php
// Configure this PHP file in Hostinger Cron Jobs, every minute. Never a public URL.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$code=Illuminate\Support\Facades\Artisan::call('minitz:work');echo Illuminate\Support\Facades\Artisan::output();exit($code);
