#!/usr/bin/env bash
# Run only from the owner's authenticated Hostinger SSH session.
set -euo pipefail
umask 077
commit="${1:-}"
[[ "$commit" =~ ^[a-f0-9]{40}$ ]] || { echo "Supply the exact release commit SHA."; exit 1; }
root="$HOME/domains/minitz.shop/public_html"
[[ "$(realpath "$root")" == "/home/u112743126/domains/minitz.shop/public_html" ]] || { echo "Unexpected hosting path; stopped."; exit 1; }
[[ -f "$root/application/.env" && -f "$root/application/vendor/autoload.php" ]] || { echo "Existing configured application not found; stopped."; exit 1; }
backup="$HOME/minitz-backups/$(date -u +%Y%m%dT%H%M%SZ)-$commit"
mkdir -p "$backup"
work="$(mktemp -d)"
echo "Downloading release $commit"
curl --fail --location --retry 2 "https://codeload.github.com/chiragkashyap267/minitz/zip/$commit" -o "$work/release.zip"
unzip -q "$work/release.zip" -d "$work"
release="$work/minitz-$commit"
[[ -f "$release/deployment/hostinger-check.php" ]] || { echo "Invalid release layout"; exit 1; }
# unzip inherits the private backup umask; normalize ONLY release source files
# before cp -a, which also preserves the source directory mode on public_html.
find "$release" -type d -exec chmod 755 {} +
find "$release" -type f -exec chmod 644 {} +
echo "Creating private code, uploads and database backups..."
tar --exclude='./.git' -czf "$backup/site.tar.gz" -C "$root" .
php "$release/deployment/hostinger-backup.php" "$root" "$backup/database.sql"
# Keep the site gated until source, schema and production configuration pass.
if [[ -f "$root/application/storage/private-preview-enabled" ]]; then mv "$root/application/storage/private-preview-enabled" "$backup/previous-preview-marker"; fi
if [[ -f "$root/application/storage/public-store-enabled" ]]; then mv "$root/application/storage/public-store-enabled" "$backup/previous-public-marker"; fi
trap 'rm -f "$root/application/storage/public-store-enabled" "$root/application/storage/private-preview-enabled"; echo "Publication stopped; public site gated. Backup: $backup"' ERR
cp -a "$release/." "$root/"
cd "$root/application"
php prepare.php
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
php artisan package:discover --ansi
php <<'PHP'
<?php
require 'vendor/autoload.php';
$path=getcwd().'/.env';$text=file_get_contents($path);
$values=['APP_ENV'=>'production','APP_DEBUG'=>'false','APP_URL'=>'https://minitz.in','STORE_DEMO'=>'false','STORE_CHECKOUT_ENABLED'=>'false'];
foreach($values as $key=>$value){$line=$key.'='.$value;$pattern='/^'.preg_quote($key,'/').'=[^\r\n]*$/m';$text=preg_match($pattern,$text)?preg_replace_callback($pattern,fn()=>$line,$text):rtrim($text)."\n".$line."\n";}
Dotenv\Dotenv::parse($text);$tmp=$path.'.publish-new';if(file_put_contents($tmp,$text,LOCK_EX)===false)throw new RuntimeException('Cannot write production config');chmod($tmp,0600);if(!rename($tmp,$path))throw new RuntimeException('Cannot replace production config');echo "Production environment configured; checkout remains closed until Razorpay setup.\n";
PHP
php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=LegacyBlogSeeder --force
php artisan view:clear
php artisan minitz:ready
php "$root/deployment/hostinger-check.php" "$root" | tee "$backup/storage-check.txt"
php artisan view:cache
printf '%s\n' "$commit" > storage/deployed-release
php <<'PHP'
<?php
require 'vendor/autoload.php';$app=require 'bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!app()->environment('production')||config('app.debug')||config('store.demo')||App\Services\CheckoutAvailability::available())throw new RuntimeException('Production/checkout guard check failed.');
// Retry failed jobs affected by the fixed header/template bug. Token expiry is
// checked by the worker; expired reset links are skipped, never delivered.
Illuminate\Support\Facades\DB::table('order_notifications')->where('is_demo',false)->whereIn('status',['failed','retry'])->where(fn($q)=>$q->whereNull('locked_until')->orWhere('locked_until','<=',now()))->update(['status'=>'pending','attempts'=>0,'next_attempt_at'=>null,'error_code'=>null,'updated_at'=>now()]);
echo "Production checks passed.\n";
PHP
touch storage/public-store-enabled
rm -f storage/private-preview-enabled
for domain in minitz.in minitz.shop; do
 curl --fail --silent --show-error --retry 2 "https://$domain/?publish_check=$commit" -o "$work/public-home.html"
 grep -q 'class="hero' "$work/public-home.html" || { rm -f storage/public-store-enabled; echo "Public storefront check failed; site gated."; exit 1; }
 for route in /shop /account /account/forgot-password /track-order; do
  curl --fail --silent --show-error --retry 2 "https://$domain$route?publish_check=$commit" -o "$work/public-route.html"
  if grep -qi '<title>.*Coming soon' "$work/public-route.html"; then rm -f storage/public-store-enabled; echo "A route remained gated; site gated for review."; exit 1; fi
 done
 echo "PUBLIC STOREFRONT OK: $domain"
done
php order-worker.php
printf '\nPRODUCTION PUBLISHED on both domains. Coming Soon/private preview removed.\nOnline checkout remains closed until Razorpay, approved policies and STORE_CHECKOUT_ENABLED=true are configured.\nSMTP configuration, APP_KEY, database and uploads preserved.\nBackup: %s\n' "$backup"
printf '\nKeep the existing every-minute cron for application/order-worker.php.\nAfter adding Razorpay keys, configure payment/refund webhooks and run payment tests before enabling checkout.\n'
