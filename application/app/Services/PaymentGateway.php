<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
class PaymentGateway {
 public static function client(){if(!config('store.key_id')||!config('store.key_secret'))throw new \RuntimeException('Payment gateway not configured.');return Http::withBasicAuth(config('store.key_id'),config('store.key_secret'))->timeout(20)->acceptJson();}
 // Reconcile with the provider before releasing stock. Unknown/authorised payments
 // keep the reservation; late captures on a closed order queue a full refund.
 public static function reconcile(object $o):bool {
  if(!$o->payment_order_id)return true;
  $data=self::client()->get('https://api.razorpay.com/v1/orders/'.rawurlencode($o->payment_order_id).'/payments')->throw()->json();
  if(!is_array($data['items']??null)||((int)($data['count']??count($data['items']))>count($data['items'])))throw new \RuntimeException('Incomplete provider payment list.');$safe=true;
  foreach($data['items'] as $p){if(($p['status']??'')==='captured'){OrderLifecycle::capture($o->payment_order_id,$p);$safe=false;}elseif(($p['status']??'')!=='failed')$safe=false;}
  return $safe;
 }
 public static function expire(int $limit=30):array {
  $r=['expired'=>0,'deferred'=>0];if(config('store.demo'))return $r;
  $orders=DB::table('orders')->where('status','awaiting_payment')->where(fn($q)=>$q->where('payment_expires_at','<=',now())->orWhere(fn($q)=>$q->whereNull('payment_expires_at')->where('created_at','<=',now()->subMinutes(config('store.payment_window_minutes',30)))))->orderBy('id')->limit($limit)->get();
  foreach($orders as $o){try{if(self::reconcile($o)&&OrderLifecycle::closeUnpaid($o->id))$r['expired']++;else $r['deferred']++;}catch(\Throwable $e){$r['deferred']++;}}return $r;
 }
 public static function processRefunds(int $limit=20):array {
  $result=['processed'=>0,'deferred'=>0];if(config('store.demo'))return $result;
  DB::table('order_refunds')->whereIn('status',['queued','retry','pending'])->where('attempts','>=',8)->where('locked_until','<=',now())->update(['status'=>'attention','locked_until'=>null,'error_code'=>'provider_lease_expired','updated_at'=>now()]);
  for($i=0;$i<$limit;$i++){
   $row=DB::transaction(function(){$r=DB::table('order_refunds')->whereIn('status',['queued','retry','pending'])->where('attempts','<',8)->where(fn($q)=>$q->whereNull('next_attempt_at')->orWhere('next_attempt_at','<=',now()))->where(fn($q)=>$q->whereNull('locked_until')->orWhere('locked_until','<=',now()))->orderBy('id')->lockForUpdate()->first();if(!$r)return null;DB::table('order_refunds')->where('id',$r->id)->update(['locked_until'=>now()->addMinutes(10),'attempts'=>$r->attempts+1,'next_attempt_at'=>now()->addMinutes(5),'updated_at'=>now()]);return $r;});if(!$row)break;
   try {
    if($row->provider_id)$p=self::client()->get('https://api.razorpay.com/v1/refunds/'.rawurlencode($row->provider_id))->throw()->json();
    else $p=self::client()->withHeaders(['X-Refund-Idempotency'=>$row->idempotency_key])->post('https://api.razorpay.com/v1/payments/'.rawurlencode($row->payment_id).'/refund',['amount'=>(int)$row->amount,'speed'=>'normal','receipt'=>'MZ-REF-'.$row->id])->throw()->json();
    self::recordRefund($p);$result['processed']++;
   }catch(\Throwable $e){DB::table('order_refunds')->where('id',$row->id)->whereNotIn('status',['processed','failed'])->update(['status'=>$row->attempts+1>=8?'attention':'retry','locked_until'=>null,'error_code'=>'provider_check_failed','next_attempt_at'=>now()->addMinutes(min(60,2**min(6,$row->attempts))),'updated_at'=>now()]);$result['deferred']++;}
  }return $result;
 }
 public static function recordRefund(array $p):void {
  DB::transaction(function()use($p){$r=DB::table('order_refunds')->where('payment_id',$p['payment_id']??'')->lockForUpdate()->first();if(!$r)return;
   abort_unless((int)($p['amount']??-1)===(int)$r->amount&&($p['currency']??'')==='INR'&&preg_match('/^rfnd_[a-zA-Z0-9_]+$/D',$p['id']??'')&&(!$r->provider_id||$r->provider_id===$p['id']),422);
   if($r->status==='processed')return;$state=$p['status']??'';if($r->status==='failed'&&$state==='pending')return;abort_unless(in_array($state,['pending','processed','failed']),422);
   DB::table('order_refunds')->where('id',$r->id)->update(['provider_id'=>$p['id'],'status'=>$state,'locked_until'=>null,'error_code'=>$state==='failed'?'provider_refund_failed':null,'attempts'=>0,'next_attempt_at'=>now()->addMinutes(5),'updated_at'=>now()]);$o=DB::table('orders')->where('id',$r->order_id)->lockForUpdate()->first();
   if($state==='processed'){DB::table('orders')->where('id',$o->id)->update(['status'=>'refunded','updated_at'=>now()]);OrderLifecycle::event($o,'refunded');}
   OrderNotifications::queue($o,$state==='processed'?'refunded':($state==='failed'?'refund_failed':'refund_pending'),'refund:'.$p['id'].':'.$state,['refund_id'=>$p['id']]);
  },3);
 }
}
