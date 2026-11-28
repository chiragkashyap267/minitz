<?php
// CLI-only diagnostics. Never prints credentials, raw exceptions or SMTP transcripts.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
function minitz_smtp_diagnosis(Throwable $error):array {
 $message='';for($e=$error;$e;$e=$e->getPrevious())$message.=' '.strtolower($e->getMessage());
 $category=match(true){
  str_contains($message,'authentication')||str_contains($message,'authenticate')||str_contains($message,'535')||str_contains($message,'534')=>'AUTH_REJECTED: SMTP rejected mailbox login; check the full mailbox username/password and SMTP access.',
  str_contains($message,'timed out')||str_contains($message,'timeout')=>'CONNECTION_TIMEOUT: SMTP connection or response timed out.',
  str_contains($message,'getaddrinfo')||str_contains($message,'resolve')||str_contains($message,'name or service not known')=>'DNS_FAILURE: SMTP hostname could not be resolved.',
  str_contains($message,'certificate')||str_contains($message,'crypto')||str_contains($message,'ssl')||str_contains($message,'tls')=>'TLS_FAILURE: Secure SMTP connection could not be established. Keep certificate verification enabled.',
  str_contains($message,'refused')||str_contains($message,'unreachable')||str_contains($message,'connection could not')=>'CONNECTION_REJECTED: SMTP endpoint could not be reached.',
  str_contains($message,'quota')||str_contains($message,'limit exceeded')||str_contains($message,'too many')=>'SENDING_LIMIT: Mailbox quota or sending-rate limit was reported.',
  str_contains($message,'relay')||str_contains($message,'sender')||str_contains($message,'550')||str_contains($message,'553')=>'SENDER_REJECTED: Check the active mailbox, sender address and sending permissions.',
  str_contains($message,'undefined function')||str_contains($message,'disabled')=>'PHP_RUNTIME: A required PHP function may be disabled.',
  default=>'OTHER_SMTP_ERROR: See the safe exception class below; no password or transcript was output.'
 };
 return ['result'=>$category,'exception'=>get_class($error)];
}
if(defined('MINITZ_SMTP_DIAGNOSTICS_LIBRARY_ONLY'))return;
$root=$argv[1]??getenv('HOME').'/domains/minitz.shop/public_html';
$expected='/home/u112743126/domains/minitz.shop/public_html';
if(realpath($root)!==$expected){fwrite(STDERR,"Unexpected application path; stopped.\n");exit(1);}
try {
 require $root.'/application/vendor/autoload.php';$app=require $root.'/application/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
 $cfg=config('mail.mailers.smtp');
 echo 'PHP '.PHP_VERSION.' | OpenSSL: '.(extension_loaded('openssl')?'yes':'NO').PHP_EOL;
 echo 'SMTP selected: '.(config('mail.default')==='smtp'?'yes':'no').PHP_EOL;
 echo 'Mailbox username correct: '.(($cfg['username']??'')==='customercare@minitz.in'?'yes':'NO').PHP_EOL;
 echo 'Password configured: '.(!empty($cfg['password'])?'yes':'NO').PHP_EOL;
 echo 'Scheme / port: '.($cfg['scheme']??'unset').' / '.(int)($cfg['port']??0).PHP_EOL;
 if(($cfg['host']??'')!=='smtp.hostinger.com'||!in_array((int)($cfg['port']??0),[465,587],true)||($cfg['username']??'')!=='customercare@minitz.in'||config('mail.from.address')!=='customercare@minitz.in')throw new RuntimeException('Sender or SMTP configuration differs from the expected mailbox.');
 echo "Testing SMTP login, then one self-addressed message...\n";
 $transport=Illuminate\Support\Facades\Mail::mailer('smtp')->getSymfonyTransport();
 try{
  if(method_exists($transport,'start'))$transport->start();
  echo "SMTP connection/authentication OK\n";
  Illuminate\Support\Facades\Mail::mailer('smtp')->raw('Minitz SMTP diagnostic test. This is only a connection test to the same mailbox; no customer order was created.',fn($m)=>$m->to('customercare@minitz.in')->subject('Minitz SMTP diagnostic test'));
  echo "PASS test message accepted. Confirm receipt in Inbox/Spam.\n";
 }finally{if(method_exists($transport,'stop'))try{$transport->stop();}catch(Throwable $ignored){}}
}catch(Throwable $error){foreach(minitz_smtp_diagnosis($error) as $key=>$value)echo strtoupper($key).': '.$value.PHP_EOL;exit(1);}
