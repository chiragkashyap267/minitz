<?php
require __DIR__.'/../application/vendor/autoload.php';$app=require __DIR__.'/../application/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!app()->environment('local'))throw new RuntimeException('Local tests only.');
use Illuminate\Support\Facades\{DB,Http};
use App\Services\CheckoutAvailability as A;
config(['store.demo'=>false,'store.checkout_enabled'=>false,'store.key_id'=>null,'store.key_secret'=>null,'store.webhook_secret'=>null]);Http::preventStrayRequests();view()->share('errors',new Illuminate\Support\ViewErrorBag);DB::beginTransaction();
try {
 if(A::available())throw new RuntimeException('Unconfigured production checkout was enabled.');
 $controller=new App\Http\Controllers\StoreController;$count=DB::table('orders')->count();
 $view=$controller->checkout()->render();if(!str_contains($view,'Online checkout opens shortly.'))throw new RuntimeException('Missing production checkout message.');
 $result=$controller->placeOrder(Illuminate\Http\Request::create('/checkout','POST',['name'=>'Ignored']));
 if(!str_ends_with($result->getTargetUrl(),'/checkout')||DB::table('orders')->count()!==$count)throw new RuntimeException('Closed checkout created an order.');
 config(['store.checkout_enabled'=>true]);if(A::available())throw new RuntimeException('Missing keys accepted.');
 config(['store.key_id'=>'rzp_live_fixture','store.key_secret'=>'fixture','store.webhook_secret'=>'fixture']);
 DB::table('settings')->where('key','policy_terms')->update(['value'=>'']);if(A::available())throw new RuntimeException('Missing policies accepted.');
 foreach(['support_email','policy_privacy','policy_terms','policy_shipping','policy_returns','policy_payment'] as $key)DB::table('settings')->updateOrInsert(compact('key'),['value'=>$key==='support_email'?'support@example.test':'Business-approved policy fixture.']);
 if(!A::available())throw new RuntimeException('Configured checkout was not available.');config(['store.checkout_enabled'=>false]);if(A::available())throw new RuntimeException('Explicit disabled flag ignored.');
 echo "PASS production storefront can run without payments; unavailable checkout blocks GET/POST ordering, requires explicit activation, keys and policies. No orders or provider calls made.\n";
}finally{DB::rollBack();}
