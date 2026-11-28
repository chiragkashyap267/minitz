<?php
// Read-only CLI check. Never prints addresses, credentials, hashes or reset tokens.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=getenv('HOME').'/domains/minitz.shop/public_html';
if(realpath($root)!=='/home/u112743126/domains/minitz.shop/public_html'){fwrite(STDERR,"Unexpected application path. Nothing changed.\n");exit(1);}
echo "Type the customer email now, then press Enter: ";
$email=strtolower(trim((string)fgets(STDIN)));
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>150){fwrite(STDERR,"INVALID EMAIL. Run this file again and enter only the email at its prompt. Nothing changed.\n");exit(1);}
require $root.'/application/vendor/autoload.php';$app=require $root.'/application/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
$key='customer-reset:'.hash('sha256',$email);
$exists=DB::table('customers')->where('email',$email)->exists();
echo 'Customer account found: '.($exists?'YES':'NO').PHP_EOL;
echo 'Reset attempts: '.RateLimiter::attempts($key).PHP_EOL;
echo 'Cooldown seconds: '.RateLimiter::availableIn($key).PHP_EOL;
echo 'Private preview active: '.(is_file(storage_path('private-preview-enabled'))?'YES':'NO').PHP_EOL;
echo 'Reset form handler installed: '.(class_exists(App\Http\Controllers\CustomerPasswordController::class)?'YES':'NO').PHP_EOL;
$rows=DB::table('order_notifications')->where('event','password_reset')->where('recipient',$email)->orderByDesc('id')->limit(5)->get(['id','status','attempts','error_code','created_at','sent_at']);
echo 'Reset queue for this account: '.$rows->toJson(JSON_PRETTY_PRINT).PHP_EOL;
if(!$exists)echo "NEXT: Register this email as a customer on the hosted website. Admin/mailbox accounts are separate.\n";
elseif(RateLimiter::tooManyAttempts($key,2))echo "NEXT: A reset cooldown is active. No changes were made to it.\n";
elseif($rows->isEmpty())echo "NEXT: No queued reset exists for this customer. Check the website form submission in the same domain/browser as the private admin preview.\n";
echo "Read-only check complete. No email was sent and no account was changed.\n";
