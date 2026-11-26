<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('orders',function(Blueprint $t){$t->string('courier',100)->nullable();$t->string('tracking_url',1000)->nullable();$t->index('tracking');});}
 public function down():void {Schema::table('orders',function(Blueprint $t){$t->dropIndex(['tracking']);$t->dropColumn(['courier','tracking_url']);});}
};
