<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class AdminOnly {
    public function handle(Request $request, Closure $next) {
        if (!$request->session()->get('admin_authenticated')) return redirect('/admin/login');
        $response=$next($request);
        $response->headers->set('Cache-Control','no-store, private');
        return $response;
    }
}
