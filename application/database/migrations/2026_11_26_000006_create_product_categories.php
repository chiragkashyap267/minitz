<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
return new class extends Migration {
 public function up():void {
  Schema::create('product_categories',function(Blueprint $t){$t->id();$t->string('name',60)->unique();$t->string('slug',100)->unique();$t->timestamps();});
  foreach(DB::table('products')->distinct()->pluck('category') as $raw){$name=trim($raw);if($name==='')continue;$slug=Str::slug($name)?:'category-'.Str::lower(Str::random(8));DB::table('product_categories')->insertOrIgnore(['name'=>$name,'slug'=>$slug,'created_at'=>now(),'updated_at'=>now()]);if($raw!==$name)DB::table('products')->where('category',$raw)->update(['category'=>$name]);}
 }
 public function down():void {Schema::dropIfExists('product_categories');}
};
