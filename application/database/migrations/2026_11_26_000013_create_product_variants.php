<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('product_variants',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->string('label',40);$t->unsignedInteger('price');$t->unsignedInteger('compare_price')->nullable();$t->unsignedInteger('stock')->default(0);$t->unsignedInteger('position')->default(0);$t->boolean('active')->default(true);$t->timestamps();$t->index(['product_id','active']);});}
 public function down():void {Schema::dropIfExists('product_variants');}
};
