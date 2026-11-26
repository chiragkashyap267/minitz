<?php
namespace App\Services;
final class YouTube {
 public static function videoId(?string $url):?string {
  $url=trim($url??'');if($url==='')return null;
  $p=parse_url($url);if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port']))return null;
  $host=strtolower($p['host']??'');$path=trim($p['path']??'','/');$id=null;
  if(in_array($host,['youtu.be','www.youtu.be'],true))$id=$path;
  elseif(in_array($host,['youtube.com','www.youtube.com','m.youtube.com','www.youtube-nocookie.com'],true)) {
   if($path==='watch'){parse_str($p['query']??'',$query);$id=$query['v']??null;}
   elseif(preg_match('~^(?:live|embed|shorts)/([A-Za-z0-9_-]{11})$~',$path,$m))$id=$m[1];
  }
  return is_string($id)&&preg_match('/^[A-Za-z0-9_-]{11}$/D',$id)?$id:null;
 }
}
