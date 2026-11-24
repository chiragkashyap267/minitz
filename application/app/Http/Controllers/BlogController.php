<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class BlogController extends StoreController {
 public function index(Request $r){
  $filters=$r->validate(['q'=>'nullable|string|max:120','category'=>'nullable|string|max:80']);
  $q=DB::table('blogs')->where('active',true)->where('published_at','<=',now());
  if(!empty($filters['q']))$q->where(fn($q)=>$q->where('title','like','%'.$filters['q'].'%')->orWhere('excerpt','like','%'.$filters['q'].'%'));
  if(!empty($filters['category']))$q->where('category',$filters['category']);
  return view('blogs',$this->common()+['blogs'=>$q->orderByDesc('published_at')->orderByDesc('id')->paginate(9)->withQueryString(),'categories'=>DB::table('blogs')->where('active',true)->where('published_at','<=',now())->distinct()->pluck('category')]);
 }
 public function show(string $slug){$blog=DB::table('blogs')->where('slug',$slug)->where('active',true)->where('published_at','<=',now())->first();abort_unless($blog,404);$related=DB::table('blogs')->where('active',true)->where('published_at','<=',now())->where('id','!=',$blog->id)->orderByDesc('published_at')->limit(3)->get();return view('blog',$this->common()+compact('blog','related'));}
 public function dashboard(){return view('admin',$this->common()+['section'=>'blogs','blogs'=>DB::table('blogs')->orderByDesc('updated_at')->paginate(12)]);}
 public function editor(?int $id=null){$blog=$id?DB::table('blogs')->find($id):null;if($id)abort_unless($blog,404);return view('admin',$this->common()+['section'=>'blogs','editingBlog'=>true,'blog'=>$blog]);}
 public function save(Request $r,?int $id=null){
  $current=$id?DB::table('blogs')->find($id):null;if($id)abort_unless($current,404);
  $d=$r->validate(['title'=>'required|string|max:160','slug'=>['nullable','string','max:180','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',Rule::unique('blogs')->ignore($id)],'category'=>'required|string|max:80','author'=>'required|string|max:100','excerpt'=>'required|string|max:1000','body'=>'required|string|max:60000','image_alt'=>'nullable|string|max:200','source_url'=>'nullable|url:http,https|max:1000','meta_title'=>'nullable|string|max:160','meta_description'=>'nullable|string|max:300']);
  if(empty($d['slug'])){$base=Str::slug($d['title'])?:'kitchen-story';$slug=substr($base,0,160);$suffix=1;while(DB::table('blogs')->where('slug',$slug)->when($id,fn($q)=>$q->where('id','!=',$id))->exists())$slug=substr($base,0,150).'-'.++$suffix;$d['slug']=$slug;}
  $d['active']=$r->boolean('active');$d['published_at']=$d['active']?($current?->published_at??now()):$current?->published_at;
  $d['image']=$this->image($r,$current?->image);if($r->boolean('remove_image')&&!$r->hasFile('image'))$d['image']=null;
  $d['updated_at']=now();if($id)DB::table('blogs')->where('id',$id)->update($d);else $id=DB::table('blogs')->insertGetId($d+['created_at'=>now()]);
  return redirect('/admin/blogs/'.$id.'/edit')->with('success',$d['active']?'Blog published.':'Blog saved as a draft.');
 }
 public function archive(int $id){abort_unless(DB::table('blogs')->where('id',$id)->exists(),404);DB::table('blogs')->where('id',$id)->update(['active'=>false,'updated_at'=>now()]);return redirect('/admin/blogs')->with('success','Blog moved to drafts.');}
}
