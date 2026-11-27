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
# Keep visitors on Coming Soon while installing and migrating.
if [[ -f "$root/application/storage/private-preview-enabled" ]]; then mv "$root/application/storage/private-preview-enabled" "$backup/previous-preview-marker"; fi
trap 'rm -f "$root/application/storage/private-preview-enabled"; echo "Deployment stopped. Private preview disabled. Backup: $backup"' ERR
# Release has no .env, vendor, runtime uploads or database files; retain the server copies.
cp -a "$release/." "$root/"
cd "$root/application"
php prepare.php
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
php artisan package:discover --ansi
php artisan config:clear
php artisan migrate --force
php artisan view:clear
php artisan minitz:ready
php "$root/deployment/hostinger-check.php" "$root" | tee "$backup/storage-check.txt"
php artisan view:cache
printf '%s\n' "$commit" > storage/deployed-release
# This enables an admin-session-only preview, never a public launch.
touch storage/private-preview-enabled
for domain in minitz.in minitz.shop; do
 for route in / /shop /account /track-order; do
  curl --fail --silent --show-error "https://$domain$route?deployment_check=$commit" -o "$work/public-check.html"
  grep -qi 'coming soon' "$work/public-check.html" || { rm -f storage/private-preview-enabled; echo "Public gate check failed; preview disabled."; exit 1; }
 done
 echo "PUBLIC COMING SOON OK: $domain"
done
printf '\nRelease deployed. Public Coming Soon retained on both domains.\nPrivate preview: https://minitz.in/admin/login (existing admin credentials).\nAlso sign in separately on minitz.shop when testing that domain.\nDatabase/files backup and check report: %s\n' "$backup"

printf '\nONE-TIME SETUP: hPanel > Advanced > Cron Jobs > PHP, every minute.\nScript: /home/u112743126/domains/minitz.shop/public_html/application/order-worker.php\nSMTP is needed for queued emails; Razorpay keys/webhooks for live payment/refunds.\n'
