<?php
namespace App\Http\Controllers;

use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CommerceController extends StoreController {
    public function account(Request $r) {
        $customer=Cart::customer();
        return view('account',$this->common()+['accountCustomer'=>$customer,'accountOrders'=>$customer?DB::table('orders')->where('customer_id',$customer->id)->latest('id')->paginate(10):null,'register'=>$r->boolean('register')]);
    }
    public function customerLogin(Request $r) {
        $d=$r->validate(['email'=>'required|email|max:150','password'=>'required|string|max:200']);
        $customer=DB::table('customers')->where('email',strtolower(trim($d['email'])))->first();
        if (!$customer || !Hash::check($d['password'],$customer->password)) return back()->with('error','The email or password is incorrect.')->withInput($r->only('email'));
        $r->session()->forget('order_tokens');$r->session()->regenerate(); session(['customer_id'=>$customer->id,'customer_auth_version'=>$customer->auth_version]);
        return redirect('/account')->with('success','Welcome back. Your kitchen corner is ready.');
    }
    public function customerRegister(Request $r) {
        $r->merge(['email'=>strtolower(trim((string)$r->input('email')))]);
        $d=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|max:150|unique:customers,email','password'=>'required|string|min:12|max:200|confirmed']);
        $id=DB::table('customers')->insertGetId(['name'=>$d['name'],'email'=>$d['email'],'password'=>Hash::make($d['password']),'created_at'=>now(),'updated_at'=>now()]);
        $r->session()->forget('order_tokens');$r->session()->regenerate(); session(['customer_id'=>$id,'customer_auth_version'=>1]); return redirect('/account')->with('success','Your kitchen corner is ready.');
    }
    public function customerLogout(Request $r) { $r->session()->forget(['customer_id','customer_auth_version','order_tokens']); $r->session()->regenerate(); $r->session()->regenerateToken(); return redirect('/account'); }
    public function drawer() { return $this->cartResponse(); }
    public function applyCoupon(Request $r) {
        $d=$r->validate(['code'=>'required|string|max:30']); $cart=Cart::summary();
        if (!$cart['items']) throw ValidationException::withMessages(['code'=>'Add a blend to your bag first.']);
        Cart::coupon($d['code'],$cart['subtotal'],Cart::customer()?->email);
        session(['coupon_code'=>strtoupper(trim($d['code']))]);
        return $r->expectsJson()?$this->cartResponse('Coupon applied. A little extra joy.'):back()->with('success','Coupon applied.');
    }
    public function removeCoupon(Request $r) {session()->forget('coupon_code');return $r->expectsJson()?$this->cartResponse('Coupon removed.'):back()->with('success','Coupon removed.');}
    public function recipeReceipt(string $slug) {
        $recipe=DB::table('recipes')->where('slug',$slug)->where('active',true)->first();abort_unless($recipe,404);
        return response()->json(['html'=>view('partials.recipe-receipt',compact('recipe'))->render(),'title'=>$recipe->title])->header('Cache-Control','no-store');
    }
    public function saveCoupon(Request $r,?int $id=null) {
        $current=$id?DB::table('coupons')->find($id):null;if($id)abort_unless($current,404);
        $r->merge(['code'=>strtoupper(trim((string)$r->input('code')))]);
        $d=$r->validate(['code'=>['required','regex:/^[A-Z0-9_-]{2,30}$/',Rule::unique('coupons')->ignore($id)],'type'=>['required',Rule::in(['percentage','flat'])],'value'=>'required|numeric|min:0.01|max:100000','minimum'=>'required|numeric|min:0|max:1000000','maximum_discount'=>'nullable|numeric|min:0.01|max:1000000','usage_limit'=>'nullable|integer|min:1|max:1000000','per_customer_limit'=>'nullable|integer|min:1|max:1000000','starts_at'=>'nullable|date','ends_at'=>['nullable','date',Rule::when($r->filled('starts_at'),'after:starts_at')]]);
        if ($d['type']==='percentage' && $d['value']>100) throw ValidationException::withMessages(['value'=>'A percentage discount cannot exceed 100%.']);
        foreach (['value','minimum','maximum_discount'] as $key) $d[$key]=isset($d[$key])?(int)round((float)$d[$key]*100):null;
        foreach (['starts_at','ends_at'] as $key) $d[$key]=!empty($d[$key])?\Carbon\Carbon::parse($d[$key])->toDateTimeString():null;
        $d['active']=$r->boolean('active');$d['updated_at']=now();
        if ($id) DB::table('coupons')->where('id',$id)->update($d); else DB::table('coupons')->insert($d+['created_at'=>now()]);
        return redirect('/admin/coupons')->with('success','Coupon saved.');
    }
    public function couponStatus(Request $r,int $id) {DB::table('coupons')->where('id',$id)->update(['active'=>$r->boolean('active'),'updated_at'=>now()]);return back()->with('success','Coupon status updated.');}
    public function saveVideo(Request $r,?int $id=null) {
        $current=$id?DB::table('testimonials')->find($id):null;if($id)abort_unless($current,404);
        $d=$r->validate(['title'=>'required|string|max:100','caption'=>'nullable|string|max:200','video'=>'nullable|file|mimetypes:video/mp4,video/webm|max:25600']);
        unset($d['video']);$d['video']=$current->video??null;
        if($r->hasFile('video')){$f=$r->file('video');$ext=match($f->getMimeType()){'video/mp4'=>'mp4','video/webm'=>'webm',default=>abort(422)};$name=Str::uuid().'.'.$ext;$f->move(storage_path('app/public/uploads'),$name);$d['video']='media/'.$name;}
        $d['image']=$this->image($r,$current->image??null);$d['active']=$r->boolean('active');$d['updated_at']=now();
        if($id)DB::table('testimonials')->where('id',$id)->update($d);else DB::table('testimonials')->insert($d+['created_at'=>now()]);return redirect('/admin/videos')->with('success','Video card saved.');
    }
}
