<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Cart;
class CommunityController extends StoreController {
 public function index(){return view('community',$this->common()+['communityPhotos'=>DB::table('community_photos')->where('active',true)->orderByDesc('id')->paginate(12)]);}
 public function save(Request $r){
  $customer=Cart::customer();if(!$customer)return redirect('/account')->with('error','Sign in to share your food photo.');
  $d=$r->validate(['title'=>'required|string|max:120','image'=>'required|file|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=5000,max_height=5000']);
  $image=$this->image($r,null);
  try{DB::table('community_photos')->insert(['customer_id'=>$customer->id,'title'=>$d['title'],'image'=>$image,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);}
  catch(\Throwable $e){@unlink(storage_path('app/public/uploads/'.basename($image)));throw $e;}
  return redirect('/#made-by-you')->with('success','Your food photo is on the table!');
 }
 public function dashboard(){return view('admin',$this->common()+['section'=>'community','communityPhotos'=>DB::table('community_photos')->orderByDesc('id')->paginate(20)]);}
 public function visibility(Request $r,int $id){$d=$r->validate(['active'=>'required|boolean']);abort_unless(DB::table('community_photos')->where('id',$id)->exists(),404);DB::table('community_photos')->where('id',$id)->update(['active'=>(bool)$d['active'],'updated_at'=>now()]);return redirect('/admin/community')->with('success','Photo visibility saved.');}
}
