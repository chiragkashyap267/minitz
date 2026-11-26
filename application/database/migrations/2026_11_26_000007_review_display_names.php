<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('product_reviews',fn(Blueprint $t)=>$t->string('display_name',100)->nullable());}
 public function down():void {Schema::table('product_reviews',fn(Blueprint $t)=>$t->dropColumn('display_name'));}
};
