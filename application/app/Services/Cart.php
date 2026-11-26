<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Cart {
    public static function customer(): ?object {
        return session('customer_id') ? DB::table('customers')->select('id','name','email')->find(session('customer_id')) : null;
    }

    public static function coupon(string $code, int $subtotal, ?string $email = null, bool $lock = false): array {
        $q = DB::table('coupons')->where('code', strtoupper(trim($code)));
        if ($lock) $q->lockForUpdate();
        $coupon = $q->first();
        $fail = static function (string $message) { throw ValidationException::withMessages(['code' => $message]); };
        if (!$coupon || !$coupon->active) $fail('That coupon is not available.');
        if ($coupon->starts_at && now()->lt($coupon->starts_at)) $fail('This coupon has not started yet.');
        if ($coupon->ends_at && now()->gt($coupon->ends_at)) $fail('This coupon has expired.');
        if ($subtotal < $coupon->minimum) $fail('This coupon needs an item subtotal of '.mz_money($coupon->minimum).'.');
        $uses = DB::table('coupon_redemptions')->where('coupon_id', $coupon->id)->whereIn('status',['reserved','used']);
        if ($coupon->usage_limit !== null && (clone $uses)->count() >= $coupon->usage_limit) $fail('This coupon has reached its usage limit.');
        if ($email && $coupon->per_customer_limit !== null && (clone $uses)->where('email',strtolower(trim($email)))->count() >= $coupon->per_customer_limit) $fail('This coupon has already been used the maximum number of times for this email.');
        $discount = $coupon->type === 'percentage' ? intdiv($subtotal * $coupon->value, 10000) : $coupon->value;
        if ($coupon->maximum_discount !== null) $discount = min($discount, $coupon->maximum_discount);
        return ['coupon'=>$coupon, 'discount'=>min($subtotal, max(0, $discount))];
    }

    public static function delivery(int $subtotal): int {
        $s=DB::table('settings')->whereIn('key',['shipping_paise','free_shipping_paise'])->pluck('value','key');
        $shipping=$subtotal>0?(int)($s['shipping_paise']??0):0;
        if ((int)($s['free_shipping_paise']??0)>0 && $subtotal >= (int)$s['free_shipping_paise']) $shipping=0;
        return $shipping;
    }

    public static function summary(): array {
        $items=[]; $subtotal=0; $unavailable=false;
        foreach (session('cart',[]) as $key=>$quantity) {
            $row=PackVariants::fromKey((string)$key);
            if(!$row)$unavailable=true;
            if($row){$row['quantity']=(int)$quantity;$items[]=$row;$subtotal+=$row['product']->price*$quantity;}
        }
        $discount=0; $coupon=null; $couponError=null;
        if (session('coupon_code') && $items) {
            try { ['coupon'=>$coupon,'discount'=>$discount]=self::coupon(session('coupon_code'),$subtotal,self::customer()?->email); }
            catch (ValidationException $e) { $couponError=collect($e->errors())->flatten()->first(); }
        }
        $shipping=self::delivery($subtotal);
        return compact('items','subtotal','shipping','discount','coupon','couponError','unavailable')+['total'=>$subtotal-$discount+$shipping,'count'=>array_sum(array_column($items,'quantity'))];
    }
}
