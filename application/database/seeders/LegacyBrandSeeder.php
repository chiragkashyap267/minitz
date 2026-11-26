<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LegacyBrandSeeder extends Seeder {
 public function run():void {
  // Public brand details from https://www.zappak.in/ and /about, checked 26 Nov 2026.
  $values=[
   'support_email'=>'customercare@zappak.in',
   'support_phone'=>'+91 9667814039',
   'about_text'=>'Minitz brings together food, music and the pleasure of making something yourself. Founded by Anupam Sen Gupta and Dilip Ramachandran, the brand approaches flavour with the same care they bring to rhythm and composition. Its spice blends draw on heritage kitchens and are handcrafted by women artisans. The idea is simple: help everyday cooks enjoy their time in the kitchen and make each dish their own.',
   'founders_intro'=>'Anupam Sen Gupta and Dilip Ramachandran share a love of music, culture and food. Anupam brings years of kitchen exploration; Dilip brings a musician’s ear for rhythm and a thoughtful appetite. Together, they created Minitz to make cooking a more joyful, personal experience.',
  ];
  $placeholders=[
   'about_text'=>'This is where the Minitz story will live. A little about the people, the kitchen and the idea that brought it all together.',
   'founders_intro'=>'Anupam brings an ear for balance. Dilip brings a drummer’s sense of timing. Minitz grew out of their time cooking and making music together.',
  ];
  foreach($values as $key=>$value){$current=DB::table('settings')->where('key',$key)->value('value');if($current===null||trim($current)===''||$current===($placeholders[$key]??null))DB::table('settings')->updateOrInsert(compact('key'),compact('value'));}
 }
}
