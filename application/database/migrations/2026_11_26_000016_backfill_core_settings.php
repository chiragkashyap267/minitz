<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void {
  // Only missing keys: existing merchant content, contacts and charges survive.
  foreach(config('site_defaults',[]) as $key=>$value)DB::table('settings')->insertOrIgnore(compact('key','value'));
 }
 public function down():void {}
};
