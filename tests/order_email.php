<?php
require __DIR__.'/../application/vendor/autoload.php';
$app=require __DIR__.'/../application/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!app()->environment('local'))throw new RuntimeException('Local tests only.');
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\StoreController;
use App\Mail\OrderConfirmation;
config(['store.demo'=>false,'store.webhook_secret'=>'mail-test-secret','mail.default'=>'array']);$fake=Mail::fake();DB::beginTransaction();
try{
 $token=(string)Str::uuid();$gateway='order_mail_'.Str::random(8);
 $id=DB::table('orders')->insertGetId(['token'=>$token,'checkout_token'=>(string)Str::uuid(),'name'=>'Test Cook <script>','email'=>'mail-fixture@example.test','phone'=>'9990000000','address'=>'QA street, Delhi 110001','items'=>json_encode([['name'=>'Masala <tag>','quantity'=>2,'price'=>37500]]),'subtotal'=>75000,'shipping'=>5000,'discount'=>10000,'total'=>70000,'status'=>'awaiting_payment','inventory_reserved'=>1,'payment_order_id'=>$gateway,'created_at'=>now(),'updated_at'=>now()]);
 $controller=new StoreController;
 $send=function($amount)use($controller,$gateway){$body=json_encode(['event'=>'payment.captured','payload'=>['payment'=>['entity'=>['id'=>'pay_mail_test','order_id'=>$gateway,'amount'=>$amount,'currency'=>'INR','status'=>'captured']]]]);$r=Request::create('/payments/webhook','POST',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_RAZORPAY_SIGNATURE'=>hash_hmac('sha256',$body,'mail-test-secret')],$body);return $controller->webhook($r);};
 try{$send(1);}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){}
 if($fake->sent(OrderConfirmation::class)->count())throw new RuntimeException('Rejected payment triggered mail.');
 $send(70000);$send(70000);
 if($fake->sent(OrderConfirmation::class)->count()!==1)throw new RuntimeException('Payment confirmation email missing or duplicated.');
 $mail=$fake->sent(OrderConfirmation::class)->first();if(!$mail->hasTo('mail-fixture@example.test'))throw new RuntimeException('Wrong recipient.');
 $html=$mail->render();foreach(['Masala &lt;tag&gt;','Test Cook &lt;script&gt;','QA street','₹750','₹50','₹100','₹700','/orders/'.$token] as $needle)if(!str_contains($html,$needle))throw new RuntimeException('Email detail missing: '.$needle);
 if(str_contains($html,'<script>'))throw new RuntimeException('Email content not escaped.');
 $text=view('emails.order-confirmation-text',['order'=>DB::table('orders')->find($id)])->render();if(!str_contains($text,'Masala')||!str_contains($text,'₹700'))throw new RuntimeException('Plain text details missing.');
 echo "PASS paid-order email recipient, line items, quantities, delivery/discount/totals, address, order link, escaping, plain text and duplicate-payment protection; no email left the test process.\n";
}finally{DB::rollBack();}
