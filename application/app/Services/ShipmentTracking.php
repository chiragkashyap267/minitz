<?php
namespace App\Services;
class ShipmentTracking {
 public static function safeUrl(?string $url):bool {
  if(!$url || !filter_var($url,FILTER_VALIDATE_URL))return false;
  $p=parse_url($url);$host=strtolower($p['host']??'');
  return ($p['scheme']??'')==='https' && !isset($p['user']) && !isset($p['pass']) && !isset($p['port']) && str_contains($host,'.') && !filter_var($host,FILTER_VALIDATE_IP) && !preg_match('/(?:^|\.)(localhost|local|internal|test|invalid)$/',$host);
 }
}
