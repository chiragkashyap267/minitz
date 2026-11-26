# Paste this block into the existing Hostinger SSH terminal.
if cd "$HOME/domains/minitz.shop/public_html/application" && [ -f .env ] && [ -f vendor/autoload.php ]; then
  read -r -s -p 'Password for customercare@minitz.in: ' MINITZ_MAIL_PASSWORD
  printf '\n'
  export MINITZ_MAIL_PASSWORD
  php <<'PHP'
<?php
require getcwd().'/vendor/autoload.php';
$password=getenv('MINITZ_MAIL_PASSWORD');
if(!$password||preg_match('/[\r\n\x00]/',$password)){fwrite(STDERR,"A non-empty, single-line mailbox password is required.\n");exit(1);}
$path=getcwd().'/.env';$env=file_get_contents($path);
$values=['MAIL_MAILER'=>'smtp','MAIL_SCHEME'=>'smtps','MAIL_HOST'=>'smtp.hostinger.com','MAIL_PORT'=>'465','MAIL_USERNAME'=>'customercare@minitz.in','MAIL_PASSWORD'=>$password,'MAIL_EHLO_DOMAIN'=>'minitz.in','MAIL_FROM_ADDRESS'=>'customercare@minitz.in','MAIL_FROM_NAME'=>'Minitz'];
foreach($values as $key=>$value){$line=$key.'="'.str_replace(['\\','"','$'],['\\\\','\\"','\\$'],$value).'"';$pattern='/^'.preg_quote($key,'/').'=[^\r\n]*$/m';$env=preg_match($pattern,$env)?preg_replace_callback($pattern,fn()=>$line,$env):rtrim($env)."\n".$line."\n";}
$parsed=Dotenv\Dotenv::parse($env);
if(($parsed['MAIL_PASSWORD']??null)!==$password)throw new RuntimeException('Password encoding check failed. No configuration was changed.');
$backup=$path.'.before-mail';if(!is_file($backup)){if(!copy($path,$backup))throw new RuntimeException('Could not back up the private configuration.');chmod($backup,0600);}
$tmp=$path.'.smtp-new';if(file_put_contents($tmp,$env,LOCK_EX)===false)throw new RuntimeException('Could not write private configuration.');chmod($tmp,0600);
if(!rename($tmp,$path))throw new RuntimeException('Could not replace private configuration.');
echo "Hostinger SMTP configured. Password was not displayed.\n";
PHP
  MINITZ_MAIL_SETUP_RESULT=$?
  unset MINITZ_MAIL_PASSWORD
  if [ "$MINITZ_MAIL_SETUP_RESULT" -eq 0 ]; then
    php artisan config:clear
    php <<'PHP'
<?php
require getcwd().'/vendor/autoload.php';
$app=require getcwd().'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
 Illuminate\Support\Facades\Mail::raw('Minitz SMTP test: this mailbox is connected to the website. No customer order was created.',fn($m)=>$m->to('customercare@minitz.in')->subject('Minitz website — SMTP connection test'));
 echo "Test email submitted to customercare@minitz.in. Check Inbox and Spam to confirm receipt.\n";
} catch(Throwable $e){fwrite(STDERR,"SMTP test failed. Check the mailbox password, Hostinger SMTP access and sender setup; do not share the password in chat.\n");exit(1);}
PHP
  fi
else
  printf 'Application .env or Composer vendor files are missing. Nothing changed.\n'
fi
