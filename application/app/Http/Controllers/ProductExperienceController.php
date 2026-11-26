<?php
namespace App\Http\Controllers;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class ProductExperienceController extends StoreController {
 public function detail(string $slug){
  $product=DB::table('products')->where('slug',$slug)->where('active',true)->first();abort_unless($product,404);
  $gallery=DB::table('product_media')->where('product_id',$product->id)->where('active',true)->orderBy('position')->orderBy('id')->get();
  $ids=json_decode($product->related_product_ids??'[]',true)?:[];
  $related=collect();
  if($product->suggestion_mode!=='hidden')$related=DB::table('products')->where('active',true)->where('id','!=',$product->id)->when($product->suggestion_mode==='manual',fn($q)=>$q->whereIn('id',$ids))->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END',[$product->category])->orderByDesc('is_bestseller')->orderBy('featured_rank')->limit(max(1,min(8,$product->related_display_limit)))->get();
  $reviews=DB::table('product_reviews')->join('customers','customers.id','=','product_reviews.customer_id')->where('product_id',$product->id)->where('status','approved')->select('product_reviews.*','customers.name')->orderByDesc('product_reviews.id')->paginate(6,['*'],'reviews_page')->withQueryString();
  $rating=DB::table('product_reviews')->where('product_id',$product->id)->where('status','approved')->avg('rating');
  $customer=Cart::customer();$myReview=$customer?DB::table('product_reviews')->where('product_id',$product->id)->where('customer_id',$customer->id)->first():null;
  return view('product',$this->common()+compact('product','gallery','related','reviews','rating','myReview'));
 }
 public function mediaEditor(int $id){$product=DB::table('products')->find($id);abort_unless($product,404);return view('admin',$this->common()+['section'=>'product-media','product'=>$product,'gallery'=>DB::table('product_media')->where('product_id',$id)->where('active',true)->orderBy('position')->orderBy('id')->get()]);}
 public function addMedia(Request $r,int $id){
  abort_unless(DB::table('products')->where('id',$id)->exists(),404);
  $d=$r->validate(['media'=>'required|file|mimes:jpg,jpeg,png,webp,mp4,webm|max:25600','poster'=>'nullable|file|mimes:jpg,jpeg,png,webp|max:2048','alt'=>'nullable|string|max:200','position'=>'required|integer|min:0|max:10000']);
  $file=$r->file('media');$mime=$file->getMimeType();$kind=str_starts_with($mime,'image/')?'image':'video';
  $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','video/mp4'=>'mp4','video/webm'=>'webm'];abort_unless(isset($extensions[$mime]),422);
  if($kind==='image')$r->validate(['media'=>'max:2048|dimensions:max_width=5000,max_height=5000']);
  if(DB::table('product_media')->where('product_id',$id)->where('active',true)->count()>=20)return back()->with('error','Archive an existing gallery item before adding more than 20 items.');
  $name=Str::uuid().'.'.$extensions[$mime];$file->move(storage_path('app/public/uploads'),$name);
  $poster=$this->image($r,null,'poster');DB::table('product_media')->insert(['product_id'=>$id,'kind'=>$kind,'path'=>'media/'.$name,'poster'=>$poster,'alt'=>$d['alt']??'','position'=>$d['position'],'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
  return back()->with('success','Gallery item saved.');
 }
 public function updateMedia(Request $r,int $id,int $media){$d=$r->validate(['alt'=>'nullable|string|max:200','position'=>'required|integer|min:0|max:10000']);abort_unless(DB::table('product_media')->where('id',$media)->where('product_id',$id)->exists(),404);DB::table('product_media')->where('id',$media)->update($d+['updated_at'=>now()]);return back()->with('success','Gallery updated.');}
 public function archiveMedia(int $id,int $media){abort_unless(DB::table('product_media')->where('id',$media)->where('product_id',$id)->exists(),404);DB::table('product_media')->where('id',$media)->update(['active'=>false,'updated_at'=>now()]);return back()->with('success','Gallery item archived.');}
 public function saveReview(Request $r,int $id){
  $customer=Cart::customer();if(!$customer)return redirect('/account')->with('error','Sign in to share your review.');
  abort_unless(DB::table('products')->where('id',$id)->where('active',true)->exists(),404);
  $d=$r->validate(['rating'=>'required|integer|min:1|max:5','title'=>'required|string|max:120','body'=>'required|string|max:3000','photo'=>'nullable|file|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4000,max_height=4000']);
  $verified=false;foreach(DB::table('orders')->where('customer_id',$customer->id)->whereIn('status',['paid','processing','shipped','delivered'])->pluck('items') as $items)foreach(json_decode($items,true) as $item)if((int)$item['id']===$id)$verified=true;
  $old=DB::table('product_reviews')->where('product_id',$id)->where('customer_id',$customer->id)->first();$photo=$old?->photo;
  if($r->hasFile('photo')){$file=$r->file('photo');$ext=match($file->getMimeType()){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>abort(422)};$photo=Str::uuid().'.'.$ext;$file->move(storage_path('app/private/reviews'),$photo);}
  DB::table('product_reviews')->updateOrInsert(['product_id'=>$id,'customer_id'=>$customer->id],['rating'=>$d['rating'],'title'=>$d['title'],'body'=>$d['body'],'photo'=>$photo,'status'=>'pending','verified_purchase'=>$verified,'created_at'=>$old?->created_at??now(),'updated_at'=>now()]);
  return back()->with('success','Your review is saved and awaiting approval. Editing a review sends it for approval again.');
 }
 public function reviewPhoto(int $id){$review=DB::table('product_reviews')->find($id);abort_unless($review&&$review->photo,404);abort_unless($review->status==='approved'||session('admin_authenticated')||Cart::customer()?->id===$review->customer_id,404);abort_unless(preg_match('/^[a-f0-9-]+\.(jpg|png|webp)$/D',$review->photo),404);$file=storage_path('app/private/reviews/'.$review->photo);abort_unless(is_file($file),404);return response()->file($file,['X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);}
 public function reviewsAdmin(){return view('admin',$this->common()+['section'=>'reviews','reviewRows'=>DB::table('product_reviews')->join('customers','customers.id','=','product_reviews.customer_id')->join('products','products.id','=','product_reviews.product_id')->select('product_reviews.*','customers.name','products.name as product_name')->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->orderByDesc('product_reviews.id')->paginate(20)]);}
 public function moderate(Request $r,int $id){$d=$r->validate(['status'=>'required|in:pending,approved,rejected']);abort_unless(DB::table('product_reviews')->where('id',$id)->exists(),404);DB::table('product_reviews')->where('id',$id)->update($d+['updated_at'=>now()]);return back()->with('success','Review status saved.');}
}
