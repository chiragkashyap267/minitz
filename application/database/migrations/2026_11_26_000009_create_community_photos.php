<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('community_photos',function(Blueprint $t){$t->id();$t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();$t->string('title',120);$t->string('image');$t->boolean('active')->default(true)->index();$t->timestamps();});}
 public function down():void {Schema::dropIfExists('community_photos');}
};
