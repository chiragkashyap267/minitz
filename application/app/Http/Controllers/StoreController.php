<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Dompdf\Dompdf;
use App\Services\Cart;

class StoreController {
    public function settings():array { $defaults=[];foreach(config('content',[]) as $fields)foreach($fields as $key=>$field)$defaults[$key]=$field[1];return array_replace($defaults,DB::table('settings')->pluck('value','key')->all()); }
    protected function common():array { $cartSummary=Cart::summary();return ['settings'=>$this->settings(),'cartCount'=>$cartSummary['count'],'cartSummary'=>$cartSummary,'customer'=>Cart::customer(),'demo'=>config('store.demo')]; }
    protected function cartResponse(?string $message=null) { $data=$this->common();return response()->json(['html'=>view('partials.cart-drawer-content',$data+$data['cartSummary'])->render(),'count'=>$data['cartCount'],'message'=>$message])->header('Cache-Control','no-store, private'); }
    public function home() { return view('home',$this->common()+['homeReviews'=>DB::table('product_reviews')->join('customers','customers.id','=','product_reviews.customer_id')->join('products','products.id','=','product_reviews.product_id')->where('product_reviews.status','approved')->where('products.active',true)->select('product_reviews.*','customers.name','products.name as product_name','products.slug as product_slug')->orderByDesc('product_reviews.id')->limit(12)->get(),'communityPhotos'=>DB::table('community_photos')->where('active',true)->orderByDesc('id')->limit(8)->get(),'blogs'=>DB::table('blogs')->where('active',true)->where('published_at','<=',now())->orderByDesc('published_at')->limit(3)->get(),'products'=>DB::table('products')->where('active',true)->where('is_bestseller',true)->orderBy('featured_rank')->orderBy('id')->limit(4)->get(),'recipes'=>DB::table('recipes')->where('active',true)->limit(3)->get(),'videos'=>DB::table('testimonials')->where('active',true)->get()]); }
    public function shop(Request $r) {
        $q=DB::table('products')->where('active',true);
        if($r->filled('q')) $q->where('name','like','%'.mb_substr($r->string('q'),0,100).'%');
        if($r->filled('category')) $q->where('category',$r->string('category'));
        $sort=$r->get('sort'); if($sort==='price-low')$q->orderBy('price'); elseif($sort==='price-high')$q->orderByDesc('price'); else $q->orderByDesc('is_bestseller')->orderBy('featured_rank')->orderBy('id');
        return view('shop',$this->common()+['products'=>$q->paginate(12)->withQueryString(),'categories'=>DB::table('product_categories')->orderBy('name')->pluck('name')]);
    }
    public function product(string $slug){$product=DB::table('products')->where('slug',$slug)->where('active',true)->first();abort_unless($product,404);return view('product',$this->common()+compact('product'));}
    public function recipes(){return view('recipes',$this->common()+['recipes'=>DB::table('recipes')->where('active',true)->get()]);}
    public function recipe(string $slug){$recipe=DB::table('recipes')->where('slug',$slug)->where('active',true)->first();abort_unless($recipe,404);return view('recipe',$this->common()+compact('recipe'));}
    public function page(string $page){abort_unless(in_array($page,['about','contact','privacy','terms','shipping','returns','payment']),404);return view('page',$this->common()+compact('page'));}
    public function media(string $file){abort_unless(preg_match('/^[a-f0-9-]+\.(webp|png|jpg|mp4|webm)$/',$file),404);$path=storage_path('app/public/uploads/'.$file);abort_unless(is_file($path),404);return response()->file($path,['X-Content-Type-Options'=>'nosniff']);}
    public function add(Request $r){ return $this->storeCartItem($r,false); }
    public function buyNow(Request $r){ return $this->storeCartItem($r,true); }
    private function storeCartItem(Request $r,bool $checkout){
        $data=$r->validate(['id'=>'required|integer','quantity'=>'required|integer|min:1|max:20']);
        $p=DB::table('products')->where('id',$data['id'])->where('active',true)->first();abort_unless($p,404);
        $cart=session('cart',[]);$qty=($cart[$p->id]??0)+$data['quantity'];
        if($qty>min(20,$p->stock)){if($r->expectsJson())return response()->json(['message'=>'That quantity is not available.'],422);return back()->with('error','That quantity is not available.');}
        $cart[$p->id]=$qty;session(['cart'=>$cart]);if($checkout)return redirect('/checkout');return $r->expectsJson()?$this->cartResponse('A little flavour added to your bag.'):back()->with('success','A little flavour added to your bag.');
    }
    public function updateCart(Request $r){
        $data=$r->validate(['quantities'=>'required|array','quantities.*'=>'required|integer|min:0|max:20']);$cart=[];
        foreach($data['quantities'] as $id=>$qty){$p=DB::table('products')->where('id',$id)->where('active',true)->first();if($p&&$qty>0){if($qty>$p->stock){if($r->expectsJson())return response()->json(['message'=>'Available stock has changed.'],422);return back()->with('error','Available stock has changed.');}$cart[$p->id]=$qty;}}
        session(['cart'=>$cart]);return $r->expectsJson()?$this->cartResponse('Your bag is up to date.'):back()->with('success','Your bag is up to date.');
    }
    private function cartData():array { return Cart::summary(); }
    public function cart(){return view('cart',$this->common()+$this->cartData());}
    public function checkout(){if(!$this->cartData()['items'])return redirect('/cart');session()->put('checkout_token',session('checkout_token',Str::uuid()->toString()));return view('checkout',$this->common()+$this->cartData());}
    public function placeOrder(Request $r){
        $d=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|max:150','phone'=>'required|regex:/^[0-9+ ()-]{10,20}$/','line1'=>'required|string|max:200','city'=>'required|string|max:100','state'=>'required|string|max:100','pincode'=>'required|regex:/^[1-9][0-9]{5}$/','accept'=>'accepted','checkout_token'=>'required|uuid']);
        abort_unless(hash_equals((string)session('checkout_token'),$d['checkout_token']),419);
        $existing=DB::table('orders')->where('checkout_token',$d['checkout_token'])->first();if($existing)return redirect('/orders/'.$existing->token);
        if(!config('store.demo')&&(!config('store.key_id')||!config('store.key_secret')))return back()->with('error','Online checkout is not open yet.');
        $customer=Cart::customer();if($customer)$d['email']=$customer->email;else $d['email']=strtolower(trim($d['email']));
        $cart=$this->cartData();if(!$cart['items'])return redirect('/cart');
        $order=DB::transaction(function() use($d,$cart){
            $items=[];$subtotal=0;
            foreach($cart['items'] as $row){$p=DB::table('products')->where('id',$row['product']->id)->lockForUpdate()->first();abort_unless($p&&$p->active&&$p->stock>=$row['quantity'],409,'A product is no longer available in that quantity.');$items[]=['id'=>$p->id,'name'=>$p->name,'quantity'=>$row['quantity'],'price'=>$p->price,'weight'=>$p->weight];$subtotal+=$p->price*$row['quantity'];if(!config('store.demo'))DB::table('products')->where('id',$p->id)->decrement('stock',$row['quantity']);}
            $coupon=null;$discount=0;
            if(session('coupon_code'))['coupon'=>$coupon,'discount'=>$discount]=Cart::coupon(session('coupon_code'),$subtotal,$d['email'],true);
            $shipping=Cart::delivery($subtotal);
            $token=Str::uuid()->toString();$id=DB::table('orders')->insertGetId(['token'=>$token,'checkout_token'=>$d['checkout_token'],'customer_id'=>Cart::customer()?->id,'name'=>$d['name'],'email'=>$d['email'],'phone'=>$d['phone'],'address'=>implode(', ',[$d['line1'],$d['city'],$d['state'],$d['pincode']]),'items'=>json_encode($items),'subtotal'=>$subtotal,'shipping'=>$shipping,'discount'=>$discount,'coupon_id'=>$coupon?->id,'coupon_code'=>$coupon?->code,'total'=>$subtotal-$discount+$shipping,'status'=>config('store.demo')?'preview':'awaiting_payment','inventory_reserved'=>!config('store.demo'),'created_at'=>now(),'updated_at'=>now()]);
            if($coupon)DB::table('coupon_redemptions')->insert(['coupon_id'=>$coupon->id,'order_id'=>$id,'email'=>$d['email'],'status'=>config('store.demo')?'used':'reserved','created_at'=>now(),'updated_at'=>now()]);
            return DB::table('orders')->find($id);
        });
        if(!config('store.demo')){
            try{$response=Http::withBasicAuth(config('store.key_id'),config('store.key_secret'))->timeout(15)->post('https://api.razorpay.com/v1/orders',['amount'=>$order->total,'currency'=>'INR','receipt'=>'MZ-'.$order->id]);$response->throw();abort_unless($response->json('id'),502);DB::table('orders')->where('id',$order->id)->update(['payment_order_id'=>$response->json('id')]);}
            catch(\Throwable $e){$this->cancelUnpaid($order);session()->forget('checkout_token');report($e);return back()->with('error','Payment could not be started. Your bag is saved; please try again.');}
        }
        session()->push('order_tokens',$order->token);session()->forget(['cart','checkout_token','coupon_code']);return redirect('/orders/'.$order->token);
    }
    public function order(string $token){$order=DB::table('orders')->where('token',$token)->first();abort_unless($order,404);abort_unless(in_array($token,session('order_tokens',[]),true)||session('admin_authenticated')||($order->customer_id&&Cart::customer()?->id===$order->customer_id),404);return view('order',$this->common()+compact('order'));}
    public function verifyPayment(Request $r){
        $d=$r->validate(['razorpay_order_id'=>'required|string|max:100','razorpay_payment_id'=>'required|string|max:100','razorpay_signature'=>'required|string|size:64']);
        $order=DB::table('orders')->where('payment_order_id',$d['razorpay_order_id'])->first();abort_unless($order&&(in_array($order->token,session('order_tokens',[]),true)||($order->customer_id&&Cart::customer()?->id===$order->customer_id)),404);
        $expected=hash_hmac('sha256',$order->payment_order_id.'|'.$d['razorpay_payment_id'],config('store.key_secret')??'');abort_unless(hash_equals($expected,$d['razorpay_signature']),403);
        $p=Http::withBasicAuth(config('store.key_id'),config('store.key_secret'))->timeout(15)->get('https://api.razorpay.com/v1/payments/'.$d['razorpay_payment_id'])->throw()->json();
        $this->recordPayment($order->payment_order_id,$p);return redirect('/orders/'.$order->token)->with('success','Payment status updated.');
    }
    public function webhook(Request $r){
        $secret=config('store.webhook_secret');abort_unless($secret&&hash_equals(hash_hmac('sha256',$r->getContent(),$secret),(string)$r->header('X-Razorpay-Signature')),403);
        if($r->input('event')==='payment.captured')$this->recordPayment((string)$r->input('payload.payment.entity.order_id'),$r->input('payload.payment.entity',[]));return response()->json(['received'=>true]);
    }
    private function recordPayment(string $id,array $payment):void {
        $confirmed=DB::transaction(function()use($id,$payment){$o=DB::table('orders')->where('payment_order_id',$id)->lockForUpdate()->first();abort_unless($o&&($payment['order_id']??null)===$id&&($payment['amount']??-1)===$o->total&&($payment['currency']??'')==='INR',422);if(($payment['status']??'')==='captured'&&$o->status==='awaiting_payment'){DB::table('orders')->where('id',$o->id)->update(['status'=>'paid','payment_id'=>$payment['id'],'updated_at'=>now()]);return $o;}return null;});
        if($confirmed) DB::table('coupon_redemptions')->where('order_id',$confirmed->id)->where('status','reserved')->update(['status'=>'used','updated_at'=>now()]);
        if($confirmed&&!config('store.demo')) {
            try { Mail::to($confirmed->email)->send(new \App\Mail\OrderConfirmation($confirmed)); }
            catch(\Throwable $e){report($e);}
        }
    }
    private function cancelUnpaid(object $o):void {DB::transaction(function()use($o){$locked=DB::table('orders')->where('id',$o->id)->lockForUpdate()->first();if($locked->status!=='awaiting_payment')return;if($locked->inventory_reserved)foreach(json_decode($locked->items,true) as $item)DB::table('products')->where('id',$item['id'])->increment('stock',$item['quantity']);DB::table('coupon_redemptions')->where('order_id',$o->id)->where('status','reserved')->update(['status'=>'released','updated_at'=>now()]);DB::table('orders')->where('id',$o->id)->update(['status'=>'cancelled','inventory_reserved'=>false,'updated_at'=>now()]);});}
    public function login(){return view('login',$this->common());}
    public function authenticate(Request $r){$d=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!config('store.admin_hash')||strtolower($d['email'])!==strtolower(config('store.admin_email')??'')||!Hash::check($d['password'],config('store.admin_hash')))return back()->with('error','The email or password is incorrect.')->withInput($r->only('email'));$r->session()->regenerate();session(['admin_authenticated'=>true]);return redirect('/admin');}
    public function logout(Request $r){$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/admin/login');}
    public function admin(Request $r,string $section='overview'){
        abort_unless(in_array($section,['overview','products','recipes','content','orders','customers',/* 'reports', temporarily disabled */'coupons','videos','tv']),404);
        $orders=DB::table('orders')->when($r->filled('q'),fn($q)=>$q->where(fn($q)=>$q->where('name','like','%'.$r->string('q').'%')->orWhere('email','like','%'.$r->string('q').'%')))->orderByDesc('id')->paginate(15)->withQueryString();
        return view('admin',$this->common()+['coupons'=>DB::table('coupons')->orderByDesc('id')->get(),'couponUsage'=>DB::table('coupon_redemptions')->whereIn('status',['used','reserved'])->select('coupon_id',DB::raw('COUNT(*) as count'))->groupBy('coupon_id')->pluck('count','coupon_id'),'videoCards'=>DB::table('testimonials')->get(),'section'=>$section,'productCategories'=>DB::table('product_categories')->orderBy('name')->get(),'productGalleries'=>DB::table('product_media')->where('active',true)->where('kind','image')->orderBy('position')->get()->groupBy('product_id'),'products'=>DB::table('products')->orderBy('id')->get(),'recipes'=>DB::table('recipes')->get(),'orders'=>$orders,'customers'=>DB::table('orders')->select('email','name',DB::raw('COUNT(*) as order_count'),DB::raw('MAX(created_at) as last_order'))->groupBy('email','name')->paginate(15),'stats'=>['orders'=>DB::table('orders')->count(),'revenue'=>DB::table('orders')->whereIn('status',['paid','processing','shipped','delivered'])->sum('total'),'customers'=>DB::table('orders')->distinct()->count('email'),'low_stock'=>DB::table('products')->where('stock','<',5)->count()]]);
    }
    protected function image(Request $r,?string $current,string $field='image'):?string {
        if(!$r->hasFile($field))return $current;
        $r->validate([$field=>'file|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=5000,max_height=5000']);
        $file=$r->file($field);$ext=match($file->getMimeType()){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>abort(422)};
        $name=Str::uuid().'.'.$ext;$file->move(storage_path('app/public/uploads'),$name);return 'media/'.$name;
    }
    public function saveCategory(Request $r){
        $r->merge(['name'=>preg_replace('/\s+/u',' ',trim((string)$r->input('name')))]);$d=$r->validate(['name'=>'required|string|max:60']);
        if(DB::table('product_categories')->whereRaw('LOWER(name) = ?',[mb_strtolower($d['name'])])->exists())throw \Illuminate\Validation\ValidationException::withMessages(['name'=>'This category already exists.']);
        $base=Str::slug($d['name'])?:'category';$slug=$base;$index=1;while(DB::table('product_categories')->where('slug',$slug)->exists())$slug=$base.'-'.++$index;
        DB::table('product_categories')->insert($d+['slug'=>$slug,'created_at'=>now(),'updated_at'=>now()]);return redirect('/admin/products')->with('success','Category created. Select it from the product dropdown.');
    }
    public function saveProduct(Request $r,?int $id=null){
        $current=$id?DB::table('products')->find($id):null;if($id)abort_unless($current,404);
        $d=$r->validate(['name'=>'required|string|max:100','sku'=>['required','string','max:60',Rule::unique('products')->ignore($id)],'category'=>'required|string|max:60|exists:product_categories,name','price'=>'required|numeric|min:1|max:100000','compare_price'=>'nullable|numeric|min:1|max:100000|gte:price','weight'=>'required|string|max:40','description'=>'required|string|max:4000','ingredients'=>'nullable|string|max:4000','allergens'=>'nullable|string|max:2000','colour'=>['required',Rule::in(['orange','blue','green','lilac'])],'stock'=>'required|integer|min:0|max:100000']);
        $galleryFiles=\App\Services\ProductMedia::validateImages($r,$id);$extra=$r->validate(['suggestion_mode'=>'nullable|in:auto,manual,hidden','related_display_limit'=>'nullable|integer|min:1|max:8','is_bestseller'=>'nullable|boolean','featured_rank'=>'nullable|integer|min:0|max:10000','related_product_ids'=>'nullable|array|max:8','related_product_ids.*'=>'integer|distinct|exists:products,id']);$d['suggestion_mode']=$extra['suggestion_mode']??$current->suggestion_mode??'auto';$d['related_display_limit']=$extra['related_display_limit']??$current->related_display_limit??4;$d['is_bestseller']=$r->boolean('is_bestseller');$d['featured_rank']=$extra['featured_rank']??0;$d['related_product_ids']=json_encode(array_values(array_filter($extra['related_product_ids']??[],fn($related)=>(int)$related!==$id)));$d['compare_price']=isset($d['compare_price'])?(int)round($d['compare_price']*100):null;$d['price']=(int)round($d['price']*100);$d['slug']=$current->slug??Str::slug($d['name']).'-'.Str::lower(Str::random(5));$d['active']=$r->boolean('active');$d['image']=$this->image($r,$current->image??null);$d['updated_at']=now();if($id)DB::table('products')->where('id',$id)->update($d);else $id=DB::table('products')->insertGetId($d+['created_at'=>now()]);\App\Services\ProductMedia::storeImages($galleryFiles,$id,$d['name']);return redirect('/admin/products')->with('success','Product saved.');
    }
    public function saveRecipe(Request $r,?int $id=null){$current=$id?DB::table('recipes')->find($id):null;if($id)abort_unless($current,404);$d=$r->validate(['title'=>'required|string|max:100','category'=>'required|string|max:60','minutes'=>'required|integer|min:1|max:1440','servings'=>'required|integer|min:1|max:100','description'=>'required|string|max:4000','ingredients'=>'required|string|max:8000','steps'=>'required|string|max:12000']);$d['slug']=$current->slug??Str::slug($d['title']).'-'.Str::lower(Str::random(5));$d['active']=$r->boolean('active');$d['image']=$this->image($r,$current->image??null);$d['updated_at']=now();if($id)DB::table('recipes')->where('id',$id)->update($d);else DB::table('recipes')->insert($d+['created_at'=>now()]);return redirect('/admin/recipes')->with('success','Recipe saved.');}
    public function deleteItem(Request $r,string $type,int $id){abort_unless(in_array($type,['products','recipes']),404);DB::table($type)->where('id',$id)->update(['active'=>false,'updated_at'=>now()]);return back()->with('success','Item archived.');}
    public function saveBroadcast(Request $r){
        $tv=$r->validate(['tv_video_url'=>['nullable','string','max:500',function($attribute,$value,$fail){if($value&&!\App\Services\YouTube::videoId($value))$fail('Use a valid HTTPS YouTube video or live broadcast link.');}],'tv_title'=>'nullable|string|max:160','tv_enabled'=>'nullable|boolean']);
        if($r->boolean('tv_enabled')&&empty($tv['tv_video_url']))throw \Illuminate\Validation\ValidationException::withMessages(['tv_video_url'=>'Add the YouTube broadcast link before showing the player.']);
        $d=['tv_video_url'=>$tv['tv_video_url']??'','tv_video_id'=>\App\Services\YouTube::videoId($tv['tv_video_url']??'')??'','tv_title'=>$tv['tv_title']??'','tv_enabled'=>$r->boolean('tv_enabled')?'1':'0'];
        DB::transaction(function()use($d){foreach($d as $key=>$value)DB::table('settings')->updateOrInsert(compact('key'),['value'=>$value]);});
        return redirect('/admin/tv')->with('success',$r->boolean('tv_enabled')?'Broadcast saved and shown on the website.':'Broadcast hidden. The kitchen story is shown instead.');
    }
    public function saveContent(Request $r){
        $d=$r->validate(['announcement'=>'required|string|max:200','hero_heading'=>'required|string|max:100','hero_text'=>'required|string|max:500','about_heading'=>'required|string|max:150','about_text'=>'required|string|max:3000','support_email'=>'nullable|email|max:150','support_phone'=>'nullable|string|max:30','whatsapp'=>'nullable|regex:/^[0-9]{10,15}$/','instagram'=>'nullable|url:https|max:300','facebook'=>'nullable|url:https|max:300','youtube'=>'nullable|url:https|max:300','shipping_paise'=>'required|integer|min:0|max:100000','free_shipping_paise'=>'required|integer|min:0|max:10000000']);
        $copyRules=[];foreach(config('content',[]) as $fields)foreach($fields as $key=>$field)$copyRules[$key]='nullable|string|max:10000';$d+=$r->validate($copyRules);
        foreach(['story_art','blog_mascot'] as $slot)if($r->hasFile('content_images.'.$slot))$d[$slot]=$this->image($r,$this->settings()[$slot]??null,'content_images.'.$slot);
        $image=$this->image($r,$this->settings()['hero_image']??null);if($image)$d['hero_image']=$image;
        foreach(['privacy','terms','shipping','returns','payment'] as $policy){$key='policy_'.$policy;$r->validate([$key=>'nullable|string|max:20000']);$d[$key]=$r->input($key,'');}
        foreach($d as $key=>$value)DB::table('settings')->updateOrInsert(compact('key'),['value'=>$value??'']);return back()->with('success','Website content saved.');
    }
    public function updateOrder(Request $r,int $id){$d=$r->validate(['status'=>['required',Rule::in(['processing','shipped','delivered'])],'tracking'=>'nullable|string|max:200']);$o=DB::table('orders')->find($id);abort_unless($o,404);if(!in_array($o->status,['paid','processing','shipped','delivered']))return back()->with('error','Only verified paid orders can enter fulfilment.');DB::table('orders')->where('id',$id)->update($d+['updated_at'=>now()]);return back()->with('success','Order updated.');}
    public function pdf(Request $r,string $type,?int $id=null){
        abort_unless(in_array($type,['order','customers','sales']),404);$r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);
        $q=DB::table('orders')->orderByDesc('id');if($r->filled('from'))$q->whereDate('created_at','>=',$r->input('from'));if($r->filled('to'))$q->whereDate('created_at','<=',$r->input('to'));if($type==='order')$q->where('id',$id);abort_if($q->count()>500,422,'Use a shorter date range of up to 500 orders.');$orders=$q->get();if($type==='order')abort_if($orders->isEmpty(),404);
        $pdf=new Dompdf(['isRemoteEnabled'=>false,'isPhpEnabled'=>false,'defaultFont'=>'DejaVu Sans']);$pdf->loadHtml(view('pdf',compact('orders','type'))->render());$pdf->setPaper('A4');$pdf->render();return response($pdf->output(),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="minitz-'.$type.($id?'-'.$id:'').'.pdf"','Cache-Control'=>'no-store, private']);
    }
}
