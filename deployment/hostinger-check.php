<?php
// CLI only. No credentials, emails or order details are printed.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=$argv[1]??dirname(__DIR__);require $root.'/application/vendor/autoload.php';$app=require $root.'/application/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
if(!config('store.demo')&&config('store.checkout_enabled')&&!App\Services\CheckoutAvailability::available())throw new RuntimeException('Checkout is enabled but payment prerequisites are incomplete.');
foreach(['products','product_categories','product_variants','product_media','product_reviews','orders','customers','blogs','community_photos','contact_messages','newsletter_subscribers','order_requests','order_refunds','order_notifications','order_events','customer_password_resets'] as $table){if(!Schema::hasTable($table))throw new RuntimeException('Missing table: '.$table);echo 'TABLE OK '.$table.PHP_EOL;}
$key='deployment_probe_'.bin2hex(random_bytes(10));
try {DB::table('settings')->insert(['key'=>$key,'value'=>'storage-test']);DB::purge();if(DB::table('settings')->where('key',$key)->value('value')!=='storage-test')throw new RuntimeException('Database persistence failed.');echo "DATABASE persistent write/read OK\n";}
finally {DB::table('settings')->where('key',$key)->delete();}
foreach(['app/public/uploads','app/private/reviews','framework/sessions','framework/views','logs','../bootstrap/cache'] as $dir){$path=storage_path($dir);if(!is_dir($path)&&!mkdir($path,0755,true)&&!is_dir($path))throw new RuntimeException('Cannot create directory');$probe=$path.'/deployment-probe-'.bin2hex(random_bytes(8));try{if(file_put_contents($probe,'minitz-storage-check')===false||file_get_contents($probe)!=='minitz-storage-check')throw new RuntimeException('Storage read/write failed: '.$dir);echo 'STORAGE OK '.$dir.PHP_EOL;}finally{if(is_file($probe))unlink($probe);}}
view()->share('errors',new Illuminate\Support\ViewErrorBag);
$html=(new App\Http\Controllers\StoreController)->home()->render();if(!str_contains($html,'class="hero'))throw new RuntimeException('Homepage failed to render');echo "HOMEPAGE render OK\n";
echo 'UPLOAD PHP limits: '.ini_get('upload_max_filesize').' / '.ini_get('post_max_size').PHP_EOL;
echo "PASS database and file storage checks. Test uploads and checkout through authenticated preview as well.\n";
