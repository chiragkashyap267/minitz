#!/usr/bin/env bash
set -euo pipefail
umask 077
commit="${1:-}"
[[ "$commit" =~ ^[a-f0-9]{40}$ ]] || exit 1
root="$HOME/domains/minitz.shop/public_html"
[[ "$(realpath "$root")" == "/home/u112743126/domains/minitz.shop/public_html" ]] || exit 1
work="$(mktemp -d)"
backup="$HOME/minitz-backups/settings-fix-$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$backup"
files=(application/config/site_defaults.php application/app/Http/Controllers/StoreController.php application/database/migrations/2026_11_26_000016_backfill_core_settings.php)
for file in "${files[@]}"; do
 mkdir -p "$work/$(dirname "$file")" "$backup/$(dirname "$file")"
 curl -fsSL "https://raw.githubusercontent.com/chiragkashyap267/minitz/$commit/$file" -o "$work/$file"
 php -l "$work/$file"
done
for file in "${files[@]}"; do
 if [[ -f "$root/$file" ]]; then cp -p "$root/$file" "$backup/$file"; fi
 cp "$work/$file" "$root/$file"
 chmod 644 "$root/$file"
done
cd "$root/application"
php artisan config:clear
php artisan migrate --force
php artisan view:clear
php artisan view:cache
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); view()->share("errors",new Illuminate\Support\ViewErrorBag); $html=(new App\Http\Controllers\StoreController)->home()->render(); if(!str_contains($html,"class=\"hero"))throw new RuntimeException("Homepage verification failed"); echo "PASS hosted homepage renders with database settings.\n";'
echo "Settings fix installed. Existing content preserved; public Coming Soon gate unchanged."
