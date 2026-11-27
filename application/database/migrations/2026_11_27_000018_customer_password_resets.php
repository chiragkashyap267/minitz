<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('customers',function(Blueprint $t){$t->unsignedInteger('auth_version')->default(1);});Schema::create('customer_password_resets',function(Blueprint $t){$t->string('email',150)->primary();$t->string('token_hash',64);$t->timestamp('expires_at');$t->timestamps();});}
 public function down():void {Schema::dropIfExists('customer_password_resets');Schema::table('customers',fn(Blueprint $t)=>$t->dropColumn('auth_version'));}
};
