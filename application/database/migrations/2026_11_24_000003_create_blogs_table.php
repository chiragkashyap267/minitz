<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('blogs',function(Blueprint $t){$t->id();$t->string('title',160);$t->string('slug',180)->unique();$t->string('category',80);$t->string('author',100);$t->text('excerpt');$t->longText('body');$t->string('image')->nullable();$t->string('image_alt',200)->nullable();$t->string('source_url',1000)->nullable();$t->string('meta_title',160)->nullable();$t->string('meta_description',300)->nullable();$t->boolean('active')->default(false)->index();$t->timestamp('published_at')->nullable()->index();$t->timestamps();}); }
 public function down():void { Schema::dropIfExists('blogs'); }
};
