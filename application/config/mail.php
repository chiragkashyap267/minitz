<?php
return [
 'default'=>env('MAIL_MAILER','log'),
 'mailers'=>[
  'smtp'=>['transport'=>'smtp','scheme'=>env('MAIL_SCHEME'),'host'=>env('MAIL_HOST'),'port'=>(int)env('MAIL_PORT',587),'username'=>env('MAIL_USERNAME'),'password'=>env('MAIL_PASSWORD'),'timeout'=>15,'local_domain'=>env('MAIL_EHLO_DOMAIN')],
  'log'=>['transport'=>'log','channel'=>'single'],
  'array'=>['transport'=>'array'],
 ],
 'from'=>['address'=>env('MAIL_FROM_ADDRESS','hello@example.test'),'name'=>env('MAIL_FROM_NAME','Minitz')],
];
