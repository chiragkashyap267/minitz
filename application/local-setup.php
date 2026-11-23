<?php
if (PHP_SAPI !== 'cli') exit(1);
require __DIR__.'/prepare.php';
if (!is_file(__DIR__.'/.env')) {
    $env=file_get_contents(__DIR__.'/.env.example');
    $env=str_replace(['APP_ENV=production','APP_DEBUG=false','APP_URL=https://minitz.in','DB_CONNECTION=mysql','DB_DATABASE=','SESSION_SECURE_COOKIE=true'],['APP_ENV=local','APP_DEBUG=false','APP_URL=http://127.0.0.1:8087','DB_CONNECTION=sqlite','DB_DATABASE="'.str_replace('\\','/',__DIR__).'/database/database.sqlite"','SESSION_SECURE_COOKIE=false'],$env);
    $env=str_replace('APP_KEY=','APP_KEY=base64:'.base64_encode(random_bytes(32)),$env);
    file_put_contents(__DIR__.'/.env',$env);
    touch(__DIR__.'/database/database.sqlite');
}
file_put_contents(__DIR__.'/storage/installed','local-preview');
echo "Local environment ready.\n";
