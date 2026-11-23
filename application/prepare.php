<?php
// CLI only: create runtime directories before Composer or first deployment.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
foreach (['bootstrap/cache','storage/app/private','storage/app/public/uploads','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs'] as $directory) {
    if (!is_dir(__DIR__.'/'.$directory)) mkdir(__DIR__.'/'.$directory,0755,true);
}
echo "Runtime directories ready.\n";
