<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\{Cart,OrderLifecycle,PaymentGateway,OrderNotifications};
class OrderActionsController extends StoreController {
 private function owned(string $token):object {$o=DB::table('orders')->where('token',$token)->first();abort_unless($o&&(in_array($token,session('order_tokens',[]),true)||session('admin_authenticated')||($o->customer_id&&Cart::customer()?->id===$o->customer_id)),404);return $o;}
 public function emailAccess(Request $r,string $token){abort_unless($r->hasValidSignature(),403);abort_unless(DB::table('orders')->where('token',$token)->exists(),404);$tokens=session('order_tokens',[]);if(!in_array($token,$tokens,true)){$tokens[]=$token;session(['order_tokens'=>array_slice($tokens,-30)]);}$r->session()->regenerate();return redirect('/orders/'.$token)->header('Cache-Control','no-store, private')->header('Referrer-Policy','no-referrer');}
 public function requestChange(Request $r,string $token){$o=$this->owned($token);$d=$r->validate(['kind'=>'required|in:cancel,return','reason'=>'required|string|min:5|max:1000']);
  if($d['kind']==='cancel'&&$o->status==='awaiting_payment'){
   try{$safe=PaymentGateway::reconcile($o);}catch(\Throwable $e){return back()->with('error','We could not verify payment yet. Please try again shortly or contact support.');}
   if($safe&&OrderLifecycle::closeUnpaid($o->id,'cancelled'))return back()->with('success','Unpaid order cancelled. Reserved stock released.');
   $o=DB::table('orders')->find($o->id);if($o->status==='awaiting_payment')return back()->with('error','Payment is still being confirmed. Contact support before cancelling.');
  }
  OrderLifecycle::request($o->id,$d['kind'],$d['reason']);return back()->with('success','Request received. The team will review it; an email update has been queued.');
 }
 public function decide(Request $r,int $id,int $request){$d=$r->validate(['action'=>'required|in:approve,reject,receive','note'=>'required|string|min:5|max:2000','restock'=>'nullable|boolean']);OrderLifecycle::decide($id,$request,$d['action'],$d['note'],$r->boolean('restock'));return back()->with('success','Request updated. Customer email queued. Approved refunds are handled by the order worker.');}
 public function cancel(Request $r,int $id){$d=$r->validate(['note'=>'required|string|min:5|max:2000']);$o=DB::table('orders')->find($id);abort_unless($o,404);
  if($o->status==='awaiting_payment'){try{$safe=PaymentGateway::reconcile($o);}catch(\Throwable $e){return back()->with('error','Payment status could not be verified. No cancellation performed.');}if($safe&&OrderLifecycle::closeUnpaid($id,'cancelled'))return back()->with('success','Unpaid order cancelled.');}
  DB::transaction(function()use($id,$d){$o=DB::table('orders')->where('id',$id)->lockForUpdate()->first();abort_unless(in_array($o->status,['paid','processing']),409,'This order cannot be cancelled.');$q=DB::table('order_requests')->where('order_id',$id)->where('kind','cancel')->first();if(!$q){OrderLifecycle::request($id,'cancel',$d['note']);$q=DB::table('order_requests')->where('order_id',$id)->where('kind','cancel')->first();}elseif($q->status==='rejected')DB::table('order_requests')->where('id',$q->id)->update(['status'=>'requested','updated_at'=>now()]);OrderLifecycle::decide($id,$q->id,'approve',$d['note']);},3);return back()->with('success','Order cancelled; full refund queued and customer updates queued.');
 }
 public function retryMail(int $id){abort_unless(DB::table('orders')->where('id',$id)->exists(),404);OrderNotifications::retry($id);return back()->with('success','Failed emails queued for retry. SMTP and the scheduled worker must be enabled.');}
 public function retryRefund(int $id){DB::transaction(function()use($id){$refund=DB::table('order_refunds')->where('order_id',$id)->lockForUpdate()->first();abort_unless($refund,404);abort_unless(in_array($refund->status,['retry','attention'])&&(!$refund->locked_until||now()->greaterThan($refund->locked_until)),409);DB::table('order_refunds')->where('id',$refund->id)->update(['status'=>$refund->provider_id?'pending':'queued','attempts'=>0,'next_attempt_at'=>null,'error_code'=>null,'updated_at'=>now()]);});return back()->with('success','Refund queued for provider reconciliation using the original idempotency key.');}
}
