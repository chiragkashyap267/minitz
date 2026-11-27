<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,RateLimiter};
use Illuminate\Support\Str;
use App\Services\OrderNotifications;
class CustomerPasswordController extends StoreController {
 public function forgot(){return view('password-forgot',$this->common());}
 public function send(Request $r){$d=$r->validate(['email'=>'required|email|max:150']);$email=strtolower(trim($d['email']));$rate='customer-reset:'.hash('sha256',$email);
  // Always use the same public response, including for unknown/rate-limited emails.
  if(!RateLimiter::tooManyAttempts($rate,2)){
   RateLimiter::hit($rate,3600);
   if(DB::table('customers')->where('email',$email)->exists())DB::transaction(function()use($email){$token=Str::random(64);DB::table('customer_password_resets')->upsert([['email'=>$email,'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addHour(),'created_at'=>now(),'updated_at'=>now()]],['email'],['token_hash','expires_at','updated_at']);$url=rtrim(config('app.url'),'/').'/account/reset-password/'.$token.'?email='.rawurlencode($email);OrderNotifications::reset($email,$token,$url);});
  }return back()->with('success','If an account exists for that email, a password reset link will be sent. Check your inbox and spam folder.');
 }
 public function resetPage(Request $r,string $token){abort_unless(preg_match('/^[A-Za-z0-9]{64}$/D',$token),404);return response()->view('password-reset',$this->common()+['resetToken'=>$token,'resetEmail'=>(string)$r->query('email','')])->header('Referrer-Policy','no-referrer')->header('Cache-Control','no-store, private');}
 public function reset(Request $r){$d=$r->validate(['token'=>'required|string|size:64','email'=>'required|email|max:150','password'=>'required|string|min:12|max:200|confirmed']);$email=strtolower(trim($d['email']));
  $ok=DB::transaction(function()use($d,$email){$row=DB::table('customer_password_resets')->where('email',$email)->lockForUpdate()->first();if(!$row||now()->greaterThan($row->expires_at)||!hash_equals($row->token_hash,hash('sha256',$d['token'])))return false;
   $user=DB::table('customers')->where('email',$email)->lockForUpdate()->first();if(!$user)return false;DB::table('customers')->where('id',$user->id)->update(['password'=>Hash::make($d['password']),'auth_version'=>$user->auth_version+1,'updated_at'=>now()]);DB::table('customer_password_resets')->where('email',$email)->delete();return true;
  },3);
  if(!$ok)return back()->with('error','This reset link is invalid or has expired. Request a new link.');$r->session()->forget(['customer_id','customer_auth_version','order_tokens']);$r->session()->regenerate();$r->session()->regenerateToken();return redirect('/account')->with('success','Password updated. Sign in with your new password.');
 }
}
