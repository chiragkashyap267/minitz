<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ContactController extends StoreController {
 public function save(Request $r){
  $d=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|max:150','message'=>'required|string|max:5000','source'=>'required|in:home,contact']);
  DB::table('contact_messages')->insert($d+['created_at'=>now(),'updated_at'=>now()]);
  return redirect(($d['source']==='home'?'/':'/pages/contact').'#contact-us')->with('contact_success','Thank you! Your message has been received.');
 }
 public function dashboard(){return view('admin',$this->common()+['section'=>'messages','messages'=>DB::table('contact_messages')->orderByDesc('id')->paginate(20)]);}
}
