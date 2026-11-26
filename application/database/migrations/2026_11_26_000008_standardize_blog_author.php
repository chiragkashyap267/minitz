<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void { DB::table('blogs')->update(['author'=>'Minitz Kitchen']); }
 public function down():void { /* Keep the standard author when rolling back. */ }
};
