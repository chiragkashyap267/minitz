<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LegacyBlogSeeder extends Seeder {
 public function run():void {
  $posts=json_decode(file_get_contents(database_path('data/zappak-blogs.json')),true,512,JSON_THROW_ON_ERROR);
  foreach($posts as $p){$p['published_at']=\Carbon\Carbon::parse($p['published_at'])->format('Y-m-d H:i:s');DB::table('blogs')->insertOrIgnore($p+['active'=>true,'created_at'=>now(),'updated_at'=>now()]);}
  DB::table('blogs')->where('slug','aloo-in-calcutta-biryani')->where('body','like','%short sample introduction for the new Minitz blog%')->update(['active'=>false]);
 }
}
