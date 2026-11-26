<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
 public function up():void {(new \Database\Seeders\LegacyBrandSeeder)->run();}
 public function down():void { /* Existing and imported brand content remains editable. */ }
};
