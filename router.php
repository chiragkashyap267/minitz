<?php
// Local PHP development server router. Apache uses .htaccess instead.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/application') || str_starts_with($path, '/.git')) {
    http_response_code(404); exit;
}
$file = realpath(__DIR__.$path);
if ($path !== '/' && $file && str_starts_with($file, __DIR__.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
require __DIR__.'/index.php';
