<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class NewsletterController extends StoreController {
 public function signupPage(){return view('newsletter',$this->common());}
 public function subscribe(Request $r){
  $r->merge(['email'=>mb_strtolower(trim((string)$r->input('email')))]);
  $d=$r->validate(['name'=>'nullable|string|max:100','email'=>'required|email|max:150','consent'=>'accepted','source'=>'required|in:popup,page']);
  $now=now();$row=['email'=>$d['email'],'name'=>$d['name']??null,'source'=>$d['source'],'consented_at'=>$now,'sync_status'=>'pending','created_at'=>$now,'updated_at'=>$now];
  DB::table('newsletter_subscribers')->upsert([$row],['email'],['name','source','consented_at','sync_status','updated_at']);
  $message='You’re on the list! Thanks for joining the Minitz kitchen.';
  return $r->expectsJson()?response()->json(['message'=>$message]):redirect('/newsletter')->with('newsletter_success',$message);
 }
 public function dashboard(){return view('admin',$this->common()+['section'=>'newsletter','subscribers'=>DB::table('newsletter_subscribers')->orderByDesc('id')->paginate(25)]);}
 public function export(){return response()->streamDownload(function(){
  $out=fopen('php://output','w');fputcsv($out,['Email','Name','Consent recorded','Source']);
  DB::table('newsletter_subscribers')->orderBy('id')->chunkById(500,function($rows)use($out){foreach($rows as $row){$cells=[$row->email,$row->name??'',$row->consented_at,$row->source];$cells=array_map(fn($v)=>preg_match('/^[=+@-]/',$v)?"'".$v:$v,$cells);fputcsv($out,$cells);}});fclose($out);
 },'minitz-newsletter.csv',['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'private, no-store']);}
}
