<?php
require __DIR__.'/../application/vendor/autoload.php';
$app=require __DIR__.'/../application/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!app()->environment('local')){fwrite(STDERR,"Local environment only.\n");exit(1);}
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\StoreController;
config(['store.webhook_secret'=>'local-payment-test-secret','store.demo'=>true]);
$controller=new StoreController;
DB::beginTransaction();
try {
 $orderId=DB::table('orders')->insertGetId(['token'=>Str::uuid()->toString(),'checkout_token'=>Str::uuid()->toString(),'name'=>'Payment test','email'=>'payment@example.test','phone'=>'9990000000','address'=>'Sample only','items'=>'[]','subtotal'=>37500,'shipping'=>0,'total'=>37500,'status'=>'awaiting_payment','inventory_reserved'=>1,'payment_order_id'=>'order_local_qa','created_at'=>now(),'updated_at'=>now()]);
 $send=function(array $payment,?string $signature=null)use($controller){$body=json_encode(['event'=>'payment.captured','payload'=>['payment'=>['entity'=>$payment]]]);$r=Request::create('/payments/webhook','POST',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_RAZORPAY_SIGNATURE'=>$signature??hash_hmac('sha256',$body,'local-payment-test-secret')],$body);return $controller->webhook($r);};
 $payment=['id'=>'pay_local_qa','order_id'=>'order_local_qa','amount'=>37500,'currency'=>'INR','status'=>'captured'];
 foreach([['signature', $payment,'bad'],['amount',array_replace($payment,['amount'=>1]),null],['currency',array_replace($payment,['currency'=>'USD']),null]] as [$case,$data,$signature]) {
  $rejected=false;try{$send($data,$signature);}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){$rejected=in_array($e->getStatusCode(),[403,422]);}
  if(!$rejected)throw new RuntimeException('Invalid '.$case.' accepted');
  if(DB::table('orders')->find($orderId)->status!=='awaiting_payment')throw new RuntimeException('Rejected payment mutated order');
 }
 $send($payment);$send($payment);
 $o=DB::table('orders')->find($orderId);
 if($o->status!=='paid'||$o->payment_id!=='pay_local_qa')throw new RuntimeException('Verified payment not recorded');
 echo "PASS invalid signatures, mismatched amounts/currencies, captured payment and duplicate webhook idempotency\n";
} finally {DB::rollBack();}
