<?php
require __DIR__.'/../application/vendor/autoload.php';$app=require __DIR__.'/../application/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!app()->environment('local'))throw new RuntimeException('Local tests only.');
use Illuminate\Support\Facades\DB;
$controller=new App\Http\Controllers\StoreController;view()->share('errors',new Illuminate\Support\ViewErrorBag);DB::beginTransaction();
try{
 DB::table('settings')->delete();
 $html=$controller->home()->render();if(!str_contains($html,'Kya banayein?'))throw new RuntimeException('Empty-settings homepage failed.');
 foreach(['about','contact','privacy','shipping'] as $page)$controller->page($page)->render();
 DB::table('settings')->insert(['key'=>'hero_heading','value'=>'Merchant custom title']);
 $migration=require __DIR__.'/../application/database/migrations/2026_11_26_000016_backfill_core_settings.php';$migration->up();$migration->up();
 if(DB::table('settings')->where('key','hero_heading')->value('value')!=='Merchant custom title')throw new RuntimeException('Saved heading overwritten.');
 foreach(config('site_defaults') as $key=>$default)if(!DB::table('settings')->where('key',$key)->exists())throw new RuntimeException('Missing base key: '.$key);
 if(!str_contains($controller->home()->render(),'Merchant custom title'))throw new RuntimeException('Saved heading not used.');
 echo "PASS empty-settings home/about/contact/policies, idempotent missing-key backfill, and saved merchant content preserved.\n";
}finally{DB::rollBack();}
