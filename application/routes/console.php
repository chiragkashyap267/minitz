<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
Artisan::command('minitz:admin',function(){
 $email=$this->ask('Admin email');
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)) {$this->error('A valid email is required.');return 1;}
 $password=$this->secret('Admin password at least 12 characters');
 if(strlen($password??'')<12){$this->error('Password must be at least 12 characters.');return 1;}
 $path=base_path('.env');$env=file_get_contents($path);
 foreach(['ADMIN_EMAIL'=>$email,'ADMIN_PASSWORD_HASH'=>Hash::make($password)] as $key=>$value){
  $line=$key."='".$value."'";
  $env=preg_match('/^'.$key.'=/m',$env)?preg_replace('/^'.$key.'=.*$/m',addcslashes($line,'\\$'),$env):$env."\n".$line;
 }
 file_put_contents($path,$env);$this->info('Admin configured. Run php artisan config:clear if configuration is cached.');
});
Artisan::command('minitz:ready',function(){
 if(!config('app.key')){$this->error('Generate an application key first.');return 1;}
 try { \Illuminate\Support\Facades\DB::table('products')->count(); } catch(\Throwable $e){$this->error('Configure the database and run migrations first.');return 1;}
 if(!config('store.admin_email')||!config('store.admin_hash')){$this->error('Configure your own admin with php artisan minitz:admin first.');return 1;}
 if(!config('store.demo')){
  $settings=\Illuminate\Support\Facades\DB::table('settings')->pluck('value','key');
  foreach(['support_email','policy_privacy','policy_terms','policy_shipping','policy_returns','policy_payment'] as $key)if(empty($settings[$key])){$this->error('Approved policies and support contact are required before live mode.');return 1;}
  if(!config('store.key_id')||!config('store.key_secret')||!config('store.webhook_secret')){$this->error('Complete Razorpay configuration before live mode.');return 1;}
 }
 file_put_contents(storage_path('installed'),now()->toIso8601String());$this->info('Application enabled. Preview mode: '.(config('store.demo')?'yes':'no'));
});
