<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->unique();
            $t->string('password'); $t->timestamps();
        });
        Schema::create('coupons', function (Blueprint $t) {
            $t->id(); $t->string('code', 30)->unique(); $t->string('type');
            $t->unsignedInteger('value'); $t->unsignedInteger('minimum')->default(0);
            $t->unsignedInteger('maximum_discount')->nullable();
            $t->unsignedInteger('usage_limit')->nullable(); $t->unsignedInteger('per_customer_limit')->nullable();
            $t->timestamp('starts_at')->nullable(); $t->timestamp('ends_at')->nullable();
            $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $t->string('coupon_code')->nullable(); $t->unsignedInteger('discount')->default(0);
        });
        Schema::create('coupon_redemptions', function (Blueprint $t) {
            $t->id(); $t->foreignId('coupon_id')->constrained();
            $t->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('email')->index(); $t->string('status')->default('reserved'); $t->timestamps();
            $t->index(['coupon_id','status']);
        });
        Schema::create('testimonials', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->string('caption')->nullable();
            $t->string('video')->nullable(); $t->string('image')->nullable();
            $t->boolean('active')->default(true); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('testimonials'); Schema::dropIfExists('coupon_redemptions');
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_id'); $t->dropConstrainedForeignId('coupon_id');
            $t->dropColumn(['coupon_code','discount']);
        });
        Schema::dropIfExists('coupons'); Schema::dropIfExists('customers');
    }
};
