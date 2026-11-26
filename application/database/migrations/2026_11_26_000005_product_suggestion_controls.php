<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void {
  Schema::table('products',function(Blueprint $t){$t->string('suggestion_mode')->default('auto');$t->unsignedTinyInteger('related_display_limit')->default(4);});
  foreach(DB::table('products')->whereNotNull('related_product_ids')->get(['id','related_product_ids']) as $p)if(count(json_decode($p->related_product_ids,true)?:[]))DB::table('products')->where('id',$p->id)->update(['suggestion_mode'=>'manual']);
 }
 public function down():void {Schema::table('products',fn(Blueprint $t)=>$t->dropColumn(['suggestion_mode','related_display_limit']));}
};
