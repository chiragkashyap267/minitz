<?php
require __DIR__.'/../application/app/Services/YouTube.php';
use App\Services\YouTube;
$valid=['https://www.youtube.com/watch?v=M7lc1UVf-VE','https://youtu.be/M7lc1UVf-VE?si=hello','https://www.youtube.com/live/M7lc1UVf-VE','https://www.youtube.com/embed/M7lc1UVf-VE'];
foreach($valid as $url)if(YouTube::videoId($url)!=='M7lc1UVf-VE')throw new Exception('Expected valid YouTube ID');
foreach(['https://evil.example/watch?v=M7lc1UVf-VE','https://youtube.com.evil.example/watch?v=M7lc1UVf-VE','javascript:alert(1)','http://youtube.com/watch?v=M7lc1UVf-VE','https://youtube.com/watch?v[]=M7lc1UVf-VE','https://www.youtube.com/@minitz/live','https://youtube.com/watch?v=too-short'] as $url)if(YouTube::videoId($url)!==null)throw new Exception('Unsafe or unsupported URL accepted');
echo "PASS valid broadcast links and rejected unsafe/unsupported URLs\n";
