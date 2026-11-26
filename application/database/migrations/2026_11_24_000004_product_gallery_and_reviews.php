<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void {
  Schema::table('products',function(Blueprint $t){$t->boolean('is_bestseller')->default(false)->index();$t->unsignedInteger('featured_rank')->default(0);$t->json('related_product_ids')->nullable();});
  DB::table('products')->where('sku','like','MZ-DEMO-%')->update(['is_bestseller'=>true]);
  Schema::create('product_media',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->string('kind');$t->string('path');$t->string('poster')->nullable();$t->string('alt',200)->nullable();$t->unsignedInteger('position')->default(0);$t->boolean('active')->default(true);$t->timestamps();});
  Schema::create('product_reviews',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->foreignId('customer_id')->constrained()->cascadeOnDelete();$t->unsignedTinyInteger('rating');$t->string('title',120);$t->text('body');$t->string('photo')->nullable();$t->boolean('verified_purchase')->default(false);$t->string('status')->default('pending')->index();$t->timestamps();$t->unique(['product_id','customer_id']);});
 }
 public function down():void {Schema::dropIfExists('product_reviews');Schema::dropIfExists('product_media');Schema::table('products',function(Blueprint $t){$t->dropColumn(['is_bestseller','featured_rank','related_product_ids']);});}
};
