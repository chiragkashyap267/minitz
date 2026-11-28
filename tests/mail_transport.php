<?php
require __DIR__.'/../application/vendor/autoload.php';$app=require __DIR__.'/../application/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!app()->environment('local'))throw new RuntimeException('Local tests only.');
use Illuminate\Support\Facades\{DB,Mail};
use Illuminate\Support\Str;
config(['store.demo'=>false,'mail.default'=>'smtp','mail.mailers.smtp'=>['transport'=>'array']]);Mail::purge('smtp');DB::beginTransaction();
try {
 DB::table('order_notifications')->delete();$email='mail-render-fixture@example.test';$token=Str::random(64);
 DB::table('customer_password_resets')->updateOrInsert(['email'=>$email],['token_hash'=>hash('sha256',$token),'expires_at'=>now()->addHour(),'created_at'=>now(),'updated_at'=>now()]);
 App\Services\OrderNotifications::reset($email,$token,'https://minitz.in/account/reset-password/'.$token.'?email=fixture%40example.test');
 $id=DB::table('order_notifications')->where('recipient',$email)->value('id');$r=App\Services\OrderNotifications::deliver(1);
 if($r['sent']!==1||$r['failed']!==0||DB::table('order_notifications')->find($id)->status!=='sent')throw new RuntimeException('Reset render/header delivery failed.');
 $messages=Mail::mailer('smtp')->getSymfonyTransport()->messages();$message=$messages->last()->getOriginalMessage();
 if(!str_contains($message->getHtmlBody(),$token)||!str_contains($message->getTextBody(),$token))throw new RuntimeException('Reset link missing from MIME content.');
 if($message->getHeaders()->get('Message-ID')->getBodyAsString()!=='<minitz-notice-'.$id.'@'.(parse_url(config('app.url'),PHP_URL_HOST)?:'minitz.in').'>')throw new RuntimeException('Stable Message-ID missing.');
 $oid=DB::table('orders')->insertGetId(['token'=>(string)Str::uuid(),'checkout_token'=>(string)Str::uuid(),'name'=>'Mail render QA','email'=>$email,'phone'=>'9990000000','address'=>'QA address','items'=>json_encode([['name'=>'QA spice','weight'=>'50 g','quantity'=>1,'price'=>10000]]),'subtotal'=>10000,'shipping'=>0,'total'=>10000,'status'=>'paid','created_at'=>now(),'updated_at'=>now()]);$o=DB::table('orders')->find($oid);
 App\Services\OrderNotifications::queue($o,'confirmed','render-confirmed');
 foreach(array_keys(App\Mail\OrderUpdate::TEXT) as $event)App\Services\OrderNotifications::queue($o,$event,'render-'.$event,['note'=>'QA note','tracking'=>'QA123','tracking_url'=>'https://courier.example/QA123','refund_id'=>'rfnd_QA']);
 $r=App\Services\OrderNotifications::deliver(50);if($r['failed']!==0){$bad=DB::table('order_notifications')->where('status','retry')->first();$payload=json_decode(\Illuminate\Support\Facades\Crypt::decryptString($bad->payload),true);Mail::to($email)->send(new \App\Mail\OrderUpdate((object)$payload['order'],$bad->event,$payload['extra']));throw new RuntimeException('An order update failed real rendering.');}
 if(DB::table('order_notifications')->where('status','retry')->exists())throw new RuntimeException('Rendered message left in retry.');
 echo "PASS real Laravel/Symfony MIME rendering, Message-ID callback, reset link HTML/text, confirmation and all lifecycle email templates via in-memory transport. No external email sent.\n";
}finally{DB::rollBack();Mail::purge('smtp');}
