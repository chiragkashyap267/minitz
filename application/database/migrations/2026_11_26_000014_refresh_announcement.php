<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void {
  DB::table('settings')->where('key','announcement')->where('value','A little help from Minitz. Dinner is still yours.')->update(['value'=>'Welcome to Minitz — a little kitchen magic.']);
  DB::table('settings')->insertOrIgnore(['key'=>'announcement_offer','value'=>'Use code FIRST10 at checkout']);
 }
 public function down():void {}
};
