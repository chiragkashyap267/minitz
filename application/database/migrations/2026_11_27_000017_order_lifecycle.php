<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('orders',function(Blueprint $t){$t->timestamp('payment_expires_at')->nullable()->index();$t->timestamp('delivered_at')->nullable();$t->timestamp('cancelled_at')->nullable();$t->timestamp('last_payment_failure_at')->nullable();});
  Schema::create('order_requests',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->cascadeOnDelete();$t->string('kind',20);$t->string('status',30)->default('requested');$t->text('reason');$t->text('response')->nullable();$t->boolean('restock')->default(false);$t->timestamps();$t->unique(['order_id','kind']);});
  Schema::create('order_refunds',function(Blueprint $t){$t->id();$t->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();$t->string('payment_id');$t->string('provider_id')->nullable()->unique();$t->uuid('idempotency_key')->unique();$t->unsignedInteger('amount');$t->string('reason',100);$t->string('status',30)->default('queued');$t->unsignedInteger('attempts')->default(0);$t->timestamp('next_attempt_at')->nullable();$t->timestamp('locked_until')->nullable();$t->string('error_code',60)->nullable();$t->timestamps();});
  Schema::create('order_notifications',function(Blueprint $t){$t->id();$t->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();$t->string('event_key')->unique();$t->string('event',60);$t->string('recipient');$t->longText('payload');$t->boolean('is_demo')->default(false);$t->string('status',20)->default('pending')->index();$t->unsignedInteger('attempts')->default(0);$t->timestamp('next_attempt_at')->nullable()->index();$t->timestamp('locked_until')->nullable();$t->uuid('claim_token')->nullable();$t->timestamp('sent_at')->nullable();$t->string('error_code',60)->nullable();$t->timestamps();});
  Schema::create('order_events',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->cascadeOnDelete();$t->string('event',60);$t->text('note')->nullable();$t->timestamps();});
 }
 public function down():void {foreach(['order_events','order_notifications','order_refunds','order_requests'] as $t)Schema::dropIfExists($t);Schema::table('orders',function(Blueprint $t){$t->dropColumn(['payment_expires_at','delivered_at','cancelled_at','last_payment_failure_at']);});}
};
