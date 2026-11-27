<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class PublicPreview {
 public function handle(Request $request,Closure $next){
  if(!is_file(storage_path('private-preview-enabled')))return $next($request);
  if(!$request->session()->get('admin_authenticated') && !$request->is('admin/login') && !$request->is('payments/webhook'))return response()->file(public_path('coming-soon.html'),['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store, private','X-Robots-Tag'=>'noindex, nofollow']);
  $response=$next($request);$response->headers->set('Cache-Control','no-store, private');$response->headers->set('X-Robots-Tag','noindex, nofollow');return $response;
 }
}
