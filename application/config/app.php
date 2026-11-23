<?php
return ['name'=>env('APP_NAME','Minitz'),'env'=>env('APP_ENV','production'),'debug'=>(bool)env('APP_DEBUG',false),'url'=>env('APP_URL','https://minitz.in'),'timezone'=>env('APP_TIMEZONE','Asia/Kolkata'),'locale'=>'en','fallback_locale'=>'en','faker_locale'=>'en_US','key'=>env('APP_KEY'),'cipher'=>'AES-256-CBC','previous_keys'=>[],'maintenance'=>['driver'=>'file']];
