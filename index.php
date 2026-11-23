<?php
declare(strict_types=1);
// Public holding page. Full application is available only on the explicitly
// enabled local PHP development server, never through a URL/query bypass.
if (!(PHP_SAPI === 'cli-server' && getenv('MINITZ_LOCAL_PREVIEW') === '1')) {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    readfile(__DIR__.'/coming-soon.html');
    exit;
}
define('LARAVEL_START', microtime(true));
if (!is_file(__DIR__.'/application/vendor/autoload.php') || !is_file(__DIR__.'/application/storage/installed')) {
    $path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
    $manifestPath = __DIR__.'/assets/preview/manifest.json';
    $manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
    $page = $manifest[$path] ?? null;
    if (in_array(($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['GET','HEAD'], true) && is_string($page) && preg_match('/^[a-z0-9-]+\.html$/', $page) && is_file(__DIR__.'/assets/preview/'.$page)) {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache');
        readfile(__DIR__.'/assets/preview/'.$page);
        exit;
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Retry-After: 3600');
    echo '<!doctype html><html lang="en"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Minitz — opening soon</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#ffe23f;color:#173dc6;font:20px system-ui}main{max-width:600px;padding:40px}h1{font-size:90px;margin:0}p{line-height:1.6}</style><main><h1>minitz.</h1><p>A little kitchen magic is on its way.</p><p>Our new store is being set up. Check back soon.</p></main></html>';
    exit;
}
require __DIR__.'/application/vendor/autoload.php';
(require __DIR__.'/application/bootstrap/app.php')->handleRequest(Illuminate\Http\Request::capture());
