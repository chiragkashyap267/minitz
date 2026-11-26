<?php
namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class ProductMedia {
 public static function validateImages(Request $r,?int $id):array {
  $r->validate(['gallery_images'=>'nullable|array|max:7','gallery_images.*'=>'required|file|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=5000,max_height=5000']);
  $files=$r->file('gallery_images',[]);$count=$id?DB::table('product_media')->where('product_id',$id)->where('kind','image')->where('active',true)->count():0;
  if(count($files)+$count>7)throw ValidationException::withMessages(['gallery_images'=>'A product can have up to 7 extra images. Archive an existing image in Gallery before adding more.']);
  return $files;
 }
 public static function storeImages(array $files,int $id,string $name):void {
  $position=(int)DB::table('product_media')->where('product_id',$id)->max('position');
  foreach($files as $file){$ext=match($file->getMimeType()){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>abort(422)};$filename=Str::uuid().'.'.$ext;$file->move(storage_path('app/public/uploads'),$filename);DB::table('product_media')->insert(['product_id'=>$id,'kind'=>'image','path'=>'media/'.$filename,'alt'=>$name.' gallery image','position'=>++$position,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);}
 }
}
