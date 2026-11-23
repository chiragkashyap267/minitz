<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('products',function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->string('sku')->unique();$t->string('category');$t->unsignedInteger('price');$t->unsignedInteger('compare_price')->nullable();$t->string('weight');$t->text('description');$t->text('ingredients')->nullable();$t->text('allergens')->nullable();$t->string('colour')->default('orange');$t->string('image')->nullable();$t->unsignedInteger('stock')->default(0);$t->boolean('active')->default(true);$t->timestamps();});
        Schema::create('recipes',function(Blueprint $t){$t->id();$t->string('title');$t->string('slug')->unique();$t->string('category');$t->unsignedInteger('minutes')->default(30);$t->unsignedInteger('servings')->default(2);$t->text('description');$t->text('ingredients');$t->text('steps');$t->string('image')->nullable();$t->boolean('active')->default(true);$t->timestamps();});
        Schema::create('settings',function(Blueprint $t){$t->string('key')->primary();$t->text('value')->nullable();});
        Schema::create('orders',function(Blueprint $t){$t->id();$t->uuid('token')->unique();$t->uuid('checkout_token')->unique();$t->string('name');$t->string('email')->index();$t->string('phone');$t->text('address');$t->json('items');$t->unsignedInteger('subtotal');$t->unsignedInteger('shipping')->default(0);$t->unsignedInteger('total');$t->string('status')->default('preview')->index();$t->string('payment_order_id')->nullable()->unique();$t->string('payment_id')->nullable()->unique();$t->string('tracking')->nullable();$t->boolean('inventory_reserved')->default(false);$t->timestamps();});
    }
    public function down():void { foreach(['orders','settings','recipes','products'] as $table) Schema::dropIfExists($table); }
};
