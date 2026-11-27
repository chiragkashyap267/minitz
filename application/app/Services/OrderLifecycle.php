<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class OrderLifecycle {
 public static function event(object $o,string $event,?string $note=null):void {DB::table('order_events')->insert(['order_id'=>$o->id,'event'=>$event,'note'=>$note,'created_at'=>now(),'updated_at'=>now()]);}
 public static function release(object $o):void {
  if($o->inventory_reserved){foreach(json_decode($o->items,true) as $item)PackVariants::restore($item);DB::table('orders')->where('id',$o->id)->update(['inventory_reserved'=>false]);$o->inventory_reserved=false;}
  DB::table('coupon_redemptions')->where('order_id',$o->id)->whereIn('status',['reserved','used'])->update(['status'=>'released','updated_at'=>now()]);
 }
 public static function refund(object $o,string $reason):void {
  if(!$o->payment_id)return;
  DB::table('order_refunds')->insertOrIgnore(['order_id'=>$o->id,'payment_id'=>$o->payment_id,'idempotency_key'=>(string)Str::uuid(),'amount'=>$o->total,'reason'=>$reason,'status'=>config('store.demo')?'demo':'queued','created_at'=>now(),'updated_at'=>now()]);
 }
 public static function capture(string $providerOrder,array $p):void {
  DB::transaction(function()use($providerOrder,$p){
   $o=DB::table('orders')->where('payment_order_id',$providerOrder)->lockForUpdate()->first();
   abort_unless($o&&($p['order_id']??null)===$providerOrder&&(int)($p['amount']??-1)===(int)$o->total&&($p['currency']??'')==='INR'&&preg_match('/^pay_[a-zA-Z0-9_]+$/D',$p['id']??''),422);
   if(($p['status']??'')!=='captured')return;
   if($o->payment_id){abort_unless($o->payment_id===$p['id'],422);return;}
   if(!in_array($o->status,['awaiting_payment','expired','cancelled']))return;
   $closed=$o->status!=='awaiting_payment';
   DB::table('orders')->where('id',$o->id)->update(['payment_id'=>$p['id'],'status'=>$closed?$o->status:'paid','updated_at'=>now()]);$o=DB::table('orders')->find($o->id);
   if($closed){self::refund($o,'Payment captured after order closure');self::event($o,'late_payment');OrderNotifications::queue($o,'payment_review','late_payment');}
   else{DB::table('coupon_redemptions')->where('order_id',$o->id)->where('status','reserved')->update(['status'=>'used','updated_at'=>now()]);self::event($o,'paid');OrderNotifications::queue($o,'confirmed','confirmed');}
  },3);
 }
 public static function failed(array $p):void {
  if(($p['status']??'')!=='failed'||!preg_match('/^pay_[a-zA-Z0-9_]+$/D',$p['id']??''))return;
  DB::transaction(function()use($p){$o=DB::table('orders')->where('payment_order_id',$p['order_id']??'')->lockForUpdate()->first();if(!$o||$o->status!=='awaiting_payment'||$o->payment_id)return;
   if((int)($p['amount']??-1)!==(int)$o->total||($p['currency']??'')!=='INR')return;
   DB::table('orders')->where('id',$o->id)->update(['last_payment_failure_at'=>now(),'updated_at'=>now()]);OrderNotifications::queue($o,'payment_failed','failed:'.$p['id']);
  },3);
 }
 public static function closeUnpaid(int $id,string $status='expired'):bool {
  return DB::transaction(function()use($id,$status){$o=DB::table('orders')->where('id',$id)->lockForUpdate()->first();if(!$o||$o->status!=='awaiting_payment'||$o->payment_id)return false;self::release($o);DB::table('orders')->where('id',$id)->update(['status'=>$status,'cancelled_at'=>now(),'updated_at'=>now()]);$o->status=$status;self::event($o,$status);OrderNotifications::queue($o,$status,$status);return true;},3);
 }
 public static function request(int $id,string $kind,string $reason):void {
  DB::transaction(function()use($id,$kind,$reason){$o=DB::table('orders')->where('id',$id)->lockForUpdate()->first();abort_unless($o,404);
   $allowed=$kind==='cancel'?['paid','processing']:['delivered'];
   if(!in_array($o->status,$allowed))throw ValidationException::withMessages(['reason'=>'This request is not available for the current order status.']);
   if(DB::table('order_requests')->where('order_id',$id)->where('kind',$kind)->exists())throw ValidationException::withMessages(['reason'=>'This request has already been submitted.']);
   DB::table('order_requests')->insert(['order_id'=>$id,'kind'=>$kind,'reason'=>$reason,'status'=>'requested','created_at'=>now(),'updated_at'=>now()]);self::event($o,$kind.'_requested',$reason);OrderNotifications::queue($o,$kind.'_requested',$kind.'_requested');
  },3);
 }
 public static function decide(int $id,int $requestId,string $action,string $note,bool $restock=false):void {
  DB::transaction(function()use($id,$requestId,$action,$note,$restock){$o=DB::table('orders')->where('id',$id)->lockForUpdate()->first();$q=DB::table('order_requests')->where('id',$requestId)->where('order_id',$id)->lockForUpdate()->first();abort_unless($o&&$q,404);
   if($action==='receive'){
    abort_unless($q->kind==='return'&&$q->status==='approved'&&$o->status==='delivered',409,'Only an approved delivered return can be received.');
    if($restock)self::release($o);DB::table('order_requests')->where('id',$q->id)->update(['status'=>'received','restock'=>$restock,'response'=>$note,'updated_at'=>now()]);self::refund($o,'Returned items received');OrderNotifications::queue($o,'return_received','return_received',['note'=>$note]);self::event($o,'return_received',$note);return;
   }
   abort_unless($q->status==='requested',409,'Request already reviewed.');
   $event=$q->kind.'_'.($action==='approve'?'approved':'rejected');
   if($action==='approve'&&$q->kind==='cancel'){
    abort_unless(in_array($o->status,['paid','processing']),409,'Dispatched orders cannot be cancelled.');self::release($o);DB::table('orders')->where('id',$id)->update(['status'=>'cancelled','cancelled_at'=>now(),'updated_at'=>now()]);$o->status='cancelled';self::refund($o,'Approved cancellation');$event='cancelled';
   }elseif($action==='approve')abort_unless($o->status==='delivered',409);
   DB::table('order_requests')->where('id',$q->id)->update(['status'=>$action==='approve'?'approved':'rejected','response'=>$note,'updated_at'=>now()]);self::event($o,$event,$note);OrderNotifications::queue($o,$event,$event,['note'=>$note]);
  },3);
 }
 public static function fulfil(int $id,array $d):void {
  DB::transaction(function()use($id,$d){$o=DB::table('orders')->where('id',$id)->lockForUpdate()->first();abort_unless($o,404);$levels=['paid'=>0,'processing'=>1,'shipped'=>2,'delivered'=>3];
   abort_unless(isset($levels[$o->status],$levels[$d['status']])&&$levels[$d['status']]>=$levels[$o->status],409,'Order cannot move backwards or leave its current state.');
   if($d['status']==='delivered')abort_unless(in_array($o->status,['shipped','delivered']),409,'Mark the order shipped before delivered.');
   abort_if(DB::table('order_requests')->where('order_id',$id)->where('kind','cancel')->where('status','requested')->exists(),409,'Review the cancellation request before fulfilment.');
   if(in_array($d['status'],['shipped','delivered'])&&(!trim($d['tracking']??'')||!ShipmentTracking::safeUrl($d['tracking_url']??null)))throw ValidationException::withMessages(['tracking'=>'Add the AWB and full HTTPS tracking link before dispatch.']);
   $changed=$o->status!==$d['status']||($d['status']==='shipped'&&(($o->tracking??'')!==($d['tracking']??'')||($o->tracking_url??'')!==($d['tracking_url']??'')));
   if($d['status']==='delivered'&&!$o->delivered_at)$d['delivered_at']=now();DB::table('orders')->where('id',$id)->update($d+['updated_at'=>now()]);
   if($changed){$o=DB::table('orders')->find($id);self::event($o,$o->status);OrderNotifications::queue($o,$o->status,$o->status.':'.hash('sha256',($o->tracking??'').($o->tracking_url??'')),['tracking'=>$o->tracking,'tracking_url'=>$o->tracking_url,'courier'=>$o->courier]);}
  },3);
 }
}
