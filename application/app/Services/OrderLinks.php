<?php
namespace App\Services;
class OrderLinks {
 public static function email(object $order):string {return \Illuminate\Support\Facades\URL::temporarySignedRoute('order.email-access',now()->addDays(14),['token'=>$order->token]);}
}
