<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('newsletter_subscribers',function(Blueprint $t){$t->id();$t->string('email',150)->unique();$t->string('name',100)->nullable();$t->timestamp('consented_at');$t->string('source',20);$t->string('sync_status',20)->default('pending');$t->timestamps();});}
 public function down():void {Schema::dropIfExists('newsletter_subscribers');}
};
