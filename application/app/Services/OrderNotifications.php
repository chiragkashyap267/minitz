<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
class OrderNotifications {
 public static function queue(object $order,string $event,string $key,array $extra=[]):void {
  DB::table('order_notifications')->insertOrIgnore(['order_id'=>$order->id,'event_key'=>$order->id.':'.$key,'event'=>$event,'recipient'=>$order->email,'payload'=>\Illuminate\Support\Facades\Crypt::encryptString(json_encode(['order'=>(array)$order,'extra'=>$extra],JSON_THROW_ON_ERROR)),'is_demo'=>config('store.demo'),'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
 }
 public static function deliver(int $limit=30):array {
  // A log/array transport must never mark real customer notifications as sent.
  if(config('mail.default')!=='smtp')return ['sent'=>0,'failed'=>0,'paused'=>true];
  $result=['sent'=>0,'failed'=>0,'paused'=>false];
  DB::table('order_notifications')->where('status','sending')->where('attempts','>=',8)->where('locked_until','<=',now())->update(['status'=>'failed','locked_until'=>null,'error_code'=>'delivery_lease_expired','updated_at'=>now()]);
  for($i=0;$i<$limit;$i++){
   if(DB::table('order_notifications')->where('status','sent')->where('sent_at','>=',now()->subDay())->count()>=config('store.mail_daily_limit',900))break;
   $row=DB::transaction(function(){
    $row=DB::table('order_notifications')->where('is_demo',false)->whereIn('status',['pending','retry','sending'])->where('attempts','<',8)->where(fn($q)=>$q->whereNull('next_attempt_at')->orWhere('next_attempt_at','<=',now()))->where(fn($q)=>$q->whereNull('locked_until')->orWhere('locked_until','<=',now()))->orderBy('id')->lockForUpdate()->first();
    if(!$row)return null;$row->claim_token=(string)Str::uuid();DB::table('order_notifications')->where('id',$row->id)->update(['status'=>'sending','attempts'=>$row->attempts+1,'claim_token'=>$row->claim_token,'locked_until'=>now()->addMinutes(10),'updated_at'=>now()]);return $row;
   });if(!$row)break;
   try {
    $data=json_decode(\Illuminate\Support\Facades\Crypt::decryptString($row->payload),true,512,JSON_THROW_ON_ERROR);
    if($row->event==='password_reset'){
     $reset=DB::table('customer_password_resets')->where('email',$row->recipient)->first();
     if(!$reset||now()->greaterThan($reset->expires_at)||!hash_equals($reset->token_hash,hash('sha256',$data['token']))){DB::table('order_notifications')->where('id',$row->id)->where('claim_token',$row->claim_token)->update(['status'=>'superseded','locked_until'=>null]);continue;}
     $mail=new \App\Mail\CustomerPasswordReset($data['url']);
    }else{
    if($row->event==='payment_failed'&&DB::table('orders')->where('id',$row->order_id)->value('status')!=='awaiting_payment'){DB::table('order_notifications')->where('id',$row->id)->where('claim_token',$row->claim_token)->update(['status'=>'superseded','locked_until'=>null]);continue;}
    $o=(object)$data['order'];
    $mail=$row->event==='confirmed'?new \App\Mail\OrderConfirmation($o):new \App\Mail\OrderUpdate($o,$row->event,$data['extra']);
    }
    $mail->withSymfonyMessage(function($message)use($row){$message->getHeaders()->remove('Message-ID');$message->getHeaders()->addIdHeader('Message-ID','minitz-notice-'.$row->id.'@'.(parse_url(config('app.url'),PHP_URL_HOST)?:'minitz.in'));});
    Mail::to($row->recipient)->send($mail);
    DB::table('order_notifications')->where('id',$row->id)->where('claim_token',$row->claim_token)->update(['status'=>'sent','sent_at'=>now(),'locked_until'=>null,'error_code'=>null,'updated_at'=>now()]);$result['sent']++;
   }catch(\Throwable $e){
    DB::table('order_notifications')->where('id',$row->id)->where('claim_token',$row->claim_token)->update(['status'=>$row->attempts+1>=8?'failed':'retry','next_attempt_at'=>now()->addMinutes(min(60,2**min(6,$row->attempts))),'locked_until'=>null,'error_code'=>'smtp_delivery_failed','updated_at'=>now()]);$result['failed']++;
   }
  }return $result;
 }
 public static function reset(string $email,string $token,string $url):void {
  DB::table('order_notifications')->insertOrIgnore(['order_id'=>null,'event_key'=>'reset:'.hash('sha256',$token),'event'=>'password_reset','recipient'=>$email,'payload'=>\Illuminate\Support\Facades\Crypt::encryptString(json_encode(compact('token','url'),JSON_THROW_ON_ERROR)),'is_demo'=>false,'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
 }
 public static function retry(int $orderId):void {DB::table('order_notifications')->where('order_id',$orderId)->whereIn('status',['failed','retry'])->where(fn($q)=>$q->whereNull('locked_until')->orWhere('locked_until','<=',now()))->update(['status'=>'pending','attempts'=>0,'next_attempt_at'=>null,'error_code'=>null,'updated_at'=>now()]);}
}
