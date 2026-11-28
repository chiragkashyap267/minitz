<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class CheckoutAvailability {
 public static function available():bool {
  if(config('store.demo'))return true;
  if(!config('store.checkout_enabled')||!config('store.key_id')||!config('store.key_secret')||!config('store.webhook_secret'))return false;
  $s=DB::table('settings')->whereIn('key',['support_email','policy_privacy','policy_terms','policy_shipping','policy_returns','policy_payment'])->pluck('value','key');
  foreach(['support_email','policy_privacy','policy_terms','policy_shipping','policy_returns','policy_payment'] as $key)if(trim((string)($s[$key]??''))==='')return false;
  return true;
 }
}
