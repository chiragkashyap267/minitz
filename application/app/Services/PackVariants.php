<?php
namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class PackVariants {
 public static function validate(Request $r,?int $id,string $baseLabel):?array {
  if(!$r->boolean('variants_present')&&!$r->has('variants'))return null;
  $d=$r->validate(['variants'=>'nullable|array|max:11','variants.*.id'=>'nullable|integer|distinct|min:1','variants.*.label'=>'required|string|max:40','variants.*.price'=>'required|numeric|min:1|max:100000','variants.*.compare_price'=>'nullable|numeric|min:1|max:100000','variants.*.stock'=>'required|integer|min:0|max:100000']);
  $seen=[mb_strtolower(preg_replace('/\s+/u',' ',trim($baseLabel)))];$rows=[];
  foreach($d['variants']??[] as $i=>$row){
   $row['label']=preg_replace('/\s+/u',' ',trim($row['label']));$key=mb_strtolower($row['label']);
   if($key===''||in_array($key,$seen,true))throw ValidationException::withMessages(['variants.'.$i.'.label'=>'Every pack size must be different, including the default pack.']);$seen[]=$key;
   if(!empty($row['id'])&&(!$id||!DB::table('product_variants')->where('id',$row['id'])->where('product_id',$id)->exists()))throw ValidationException::withMessages(['variants.'.$i.'.id'=>'This pack does not belong to this product.']);
   if(isset($row['compare_price'])&&(float)$row['compare_price']<(float)$row['price'])throw ValidationException::withMessages(['variants.'.$i.'.compare_price'=>'MRP must be equal to or above the selling price.']);
   $rows[]=['id'=>$row['id']??null,'label'=>$row['label'],'price'=>(int)round($row['price']*100),'compare_price'=>isset($row['compare_price'])?(int)round($row['compare_price']*100):null,'stock'=>(int)$row['stock']];
  }return $rows;
 }
 public static function sync(int $productId,?array $rows):void {
  if($rows===null)return;$kept=[];
  foreach($rows as $position=>$row){$id=$row['id'];unset($row['id']);$row+=['product_id'=>$productId,'position'=>$position,'active'=>true,'updated_at'=>now()];if($id)DB::table('product_variants')->where('product_id',$productId)->where('id',$id)->update($row);else $id=DB::table('product_variants')->insertGetId($row+['created_at'=>now()]);$kept[]=$id;}
  DB::table('product_variants')->where('product_id',$productId)->when($kept,fn($q)=>$q->whereNotIn('id',$kept))->update(['active'=>false,'updated_at'=>now()]);
 }
 public static function options(object $p):array {
  $options=[['id'=>null,'label'=>$p->weight,'price'=>$p->price,'compare_price'=>$p->compare_price,'stock'=>$p->stock]];
  foreach(DB::table('product_variants')->where('product_id',$p->id)->where('active',true)->orderBy('position')->orderBy('id')->get() as $v)$options[]=['id'=>$v->id,'label'=>$v->label,'price'=>$v->price,'compare_price'=>$v->compare_price,'stock'=>$v->stock];
  return $options;
 }
 public static function defaultFor(object $p):object {
  if($p->stock>0)return $p;$v=DB::table('product_variants')->where('product_id',$p->id)->where('active',true)->where('stock','>',0)->orderBy('position')->orderBy('id')->first();
  if(!$v)return $p;$p=clone $p;$p->price=$v->price;$p->compare_price=$v->compare_price;$p->stock=$v->stock;$p->weight=$v->label;$p->selected_variant_id=$v->id;return $p;
 }
 public static function resolve(int $id,?int $variantId=null,bool $lock=false):?array {
  $q=DB::table('products')->where('id',$id)->where('active',true);if($lock)$q->lockForUpdate();$p=$q->first();if(!$p)return null;
  if($variantId){$q=DB::table('product_variants')->where('id',$variantId)->where('product_id',$id)->where('active',true);if($lock)$q->lockForUpdate();$v=$q->first();if(!$v)return null;$p->price=$v->price;$p->compare_price=$v->compare_price;$p->stock=$v->stock;$p->weight=$v->label;}
  return ['product'=>$p,'variant_id'=>$variantId,'key'=>$variantId?$id.':v'.$variantId:(string)$id];
 }
 public static function fromKey(string $key,bool $lock=false):?array {if(!preg_match('/^([1-9][0-9]*)(?::v([1-9][0-9]*))?$/D',$key,$m))return null;return self::resolve((int)$m[1],isset($m[2])?(int)$m[2]:null,$lock);}
 public static function reserve(array $row,int $quantity):void {if($row['variant_id'])DB::table('product_variants')->where('id',$row['variant_id'])->where('product_id',$row['product']->id)->decrement('stock',$quantity);else DB::table('products')->where('id',$row['product']->id)->decrement('stock',$quantity);}
 public static function restore(array $item):void {if(!empty($item['variant_id']))DB::table('product_variants')->where('id',$item['variant_id'])->where('product_id',$item['id'])->increment('stock',$item['quantity']);else DB::table('products')->where('id',$item['id'])->increment('stock',$item['quantity']);}
}
