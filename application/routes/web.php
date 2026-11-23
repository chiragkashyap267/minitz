<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController as S;
use App\Http\Controllers\CommerceController as C;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
foreach(['cart'=>60,'checkout'=>10,'admin-login'=>5,'customer-auth'=>10,'payments'=>20,'webhook'=>120] as $name=>$limit) {
 RateLimiter::for($name,fn(\Illuminate\Http\Request $r)=>Limit::perMinute($limit)->by($name.'|'.$r->ip()));
}
Route::get('/',[S::class,'home']);
Route::get('/shop',[S::class,'shop']);
Route::get('/shop/{slug}',[S::class,'product']);
Route::get('/recipes',[S::class,'recipes']);
Route::get('/recipes/{slug}',[S::class,'recipe']);
Route::get('/recipes/{slug}/receipt',[C::class,'recipeReceipt']);
Route::get('/account',[C::class,'account']);
Route::post('/account/login',[C::class,'customerLogin'])->middleware('throttle:customer-auth');
Route::post('/account/register',[C::class,'customerRegister'])->middleware('throttle:customer-auth');
Route::post('/account/logout',[C::class,'customerLogout']);
Route::get('/pages/{page}',[S::class,'page']);
Route::get('/media/{file}',[S::class,'media']);
Route::get('/cart',[S::class,'cart']);
Route::get('/cart/drawer',[C::class,'drawer']);
Route::post('/cart/coupon',[C::class,'applyCoupon'])->middleware('throttle:cart');
Route::post('/cart/coupon/remove',[C::class,'removeCoupon']);
Route::post('/cart/add',[S::class,'add'])->middleware('throttle:cart');
Route::post('/cart/update',[S::class,'updateCart']);
Route::get('/checkout',[S::class,'checkout']);
Route::post('/checkout',[S::class,'placeOrder'])->middleware('throttle:checkout');
Route::get('/orders/{token}',[S::class,'order']);
Route::post('/payments/verify',[S::class,'verifyPayment'])->middleware('throttle:payments');
Route::post('/payments/webhook',[S::class,'webhook'])->middleware('throttle:webhook');
Route::get('/admin/login',[S::class,'login']);
Route::post('/admin/login',[S::class,'authenticate'])->middleware('throttle:admin-login');
Route::middleware('admin')->group(function(){
 Route::post('/admin/logout',[S::class,'logout']);
 Route::get('/admin',[S::class,'admin']);
 Route::get('/admin/reports/{type}/{id?}',[S::class,'pdf'])->whereNumber('id');
 Route::post('/admin/products/{id?}',[S::class,'saveProduct'])->whereNumber('id');
 Route::post('/admin/recipes/{id?}',[S::class,'saveRecipe'])->whereNumber('id');
 Route::post('/admin/archive/{type}/{id}',[S::class,'deleteItem'])->whereNumber('id');
 Route::post('/admin/content',[S::class,'saveContent']);
 Route::post('/admin/coupons/{id?}',[C::class,'saveCoupon'])->whereNumber('id');
 Route::post('/admin/coupons/{id}/status',[C::class,'couponStatus'])->whereNumber('id');
 Route::post('/admin/videos/{id?}',[C::class,'saveVideo'])->whereNumber('id');
 Route::post('/admin/orders/{id}',[S::class,'updateOrder'])->whereNumber('id');
 Route::get('/admin/{section}',[S::class,'admin']);
});
