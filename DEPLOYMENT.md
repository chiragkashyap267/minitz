# Hostinger deployment

## Temporary coming soon mode

The public site currently serves `coming-soon.html` on every path. All application code and preview pages remain intact. `index.html` is a copy of the holding page for static-host compatibility. `.htaccess` routes public requests to the holding page, and `index.php` also defaults to it.

To edit the full application locally in PowerShell:

```powershell
$env:MINITZ_LOCAL_PREVIEW='1'
& 'C:/xampp/php/php.exe' -S 127.0.0.1:8087 router.php
```

This bypass is restricted to PHP's local development-server runtime and cannot be enabled through a visitor URL. When the store is approved for launch, remove the three labelled holding-page rewrite rules, remove the holding-page gate at the top of index.php, and restore index.html from assets/preview/home.html or remove that static entry. Then follow the activation steps below. Do not remove the application-directory access restrictions.

The repository supports two stages. Without backend configuration it serves the included read-only storefront preview. Ordering buttons are disabled and no customer information is collected. After the database and admin are configured, enable the Laravel application to use the working sample catalogue and preview checkout.

## Upload or Git deploy

Use the existing PHP/HTML website. The repository root belongs in its web document root, normally `public_html`. `index.php` is the entry point; `.htaccess` must be present. PHP 8.2 or later and the Laravel-required extensions are needed. Keep the root `.htaccess` and the `application/.htaccess` restrictions in place. Do not change the document root to the `application` directory.

The supplied ZIP includes Composer dependencies. A Git deployment excludes vendor dependencies; install them via SSH using the commands below. Until setup is complete, the new read-only preview remains available.

## Configure Laravel through SSH

From the website root:

```sh
cd application
php prepare.php
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
cp .env.example .env
```

Edit `application/.env` in the hosting file manager. Set APP_URL to the chosen primary HTTPS address; DB_HOST, DB_DATABASE, DB_USERNAME and DB_PASSWORD to the MySQL database created in hPanel. Leave STORE_DEMO=true. Keep APP_DEBUG=false and SESSION_SECURE_COOKIE=true. Never commit .env.

Then run:

```sh
php artisan key:generate --force
php artisan migrate --seed --force
php artisan minitz:admin
php artisan config:clear
php artisan minitz:ready
php artisan view:cache
```

The admin command prompts for your email and a password of at least 12 characters. There is no default production admin. The dashboard is at `/admin`. The seed creates sample products, recipes and placeholder content; it does not create customers, orders or login credentials.

After activation, verify the homepage, shop, product details, admin login, image uploads, content editing, preview checkout and private PDF downloads. Back up the database and uploads before subsequent deployments. Keep the existing .env, storage directory and database intact when updating code.

## Live payment activation

Live payment activation is a separate release step. The implemented Razorpay paths need testing with the merchant's sandbox account and its configured webhooks before processing live payments.

1. Replace sample products, prices, ingredients, allergens, packaging, recipes and legal business details.
2. In Website content, publish the business-approved policy text and real support contacts. Confirm delivery charges in paise; 100 paise equals INR 1.
3. Set Razorpay test keys and a webhook secret; register `https://PRIMARY-DOMAIN/payments/webhook` for `payment.captured`. Verify capture settings, successful/failed/pending payments, duplicate events and the actual merchant settlement workflow.
4. Configure and test SMTP for order confirmation emails. In .env set MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_SCHEME if required, and a verified MAIL_FROM_ADDRESS. Confirm sending limits. The default log mailer does not send email.
5. Paid checkout reserves stock. Unpaid reservations are not automatically released in this version; establish reconciliation/expiry handling before live launch. Refunds and failed-delivery operations remain in the merchant's existing business workflow.
6. Confirm PIN-code coverage rules, taxes and any required invoice format before live launch. This version supports a flat delivery charge and optional free-delivery threshold. PDF order summaries are not tax invoices.
7. Only after review, set merchant live keys and STORE_DEMO=false, run `php artisan config:clear`, then `php artisan minitz:ready` again. Do not enable live mode while sample product data or placeholder policies remain.

Both domains may serve the same app, but each domain has its own browser session. Choose a canonical primary domain before launch and align payment review, webhook URLs, search metadata and customer communication to it.

## Local development

```sh
cd application
composer install
php local-setup.php
php artisan migrate --seed
php artisan minitz:admin
cd ..
php -S 127.0.0.1:8087 router.php
```

The local setup uses SQLite, HTTP-only local preview cookies and sample mode. It does not create a default admin. Tests in `tests/smoke.py` expect a local-only fixture admin described in the test source; never use those credentials on a hosted account. Payment event checks use only local mocked data and roll back their database changes.

For the local customer test flow, run `php artisan minitz:demo-customer` after migrations. It creates a local sample customer, two discount codes and four square video placeholders. The account page provides a fill-sample-login button; preview checkout provides a fill-sample-delivery button. Preview orders never invoke Razorpay. The command is restricted to local preview mode, and the sample password is not a production credential.

Coupon discounts use integer paise; percentage values are stored in hundredths of a percent. The server recalculates discounts, caps, dates and usage limits at order placement under a coupon-row lock. Usage is checked per checkout email. Codes do not stack and shipping is not discounted. Preview orders consume a local preview use; real payment orders reserve one, captured payment marks it used, and cancellation through the order-cancellation logic releases a reserved use.

Kitchen videos can be replaced in `/admin/videos`. Square posters use JPG/PNG/WebP up to 2 MB; clips use MP4/WebM up to 25 MB. Configure PHP upload_max_filesize and post_max_size to accommodate clips before enabling large uploads. Real video assets and permission to publish them remain client-provided. The placeholder clips are not fabricated testimonials.

The storefront uses custom CSS and small vanilla JavaScript interactions. No paid UI kit, animation subscription or browser framework is required. Laravel and Dompdf licences are included with their packages. Original artwork permissions remain with the client.

## Food stories / blogs

Run `php artisan migrate --force` when updating an existing installation. The blog table is additive; existing products, customers and orders are preserved.

Manage stories at `/admin/blogs`: write/edit, cover upload and alt text, category, author, introduction, body, optional source link and search metadata. New stories default to drafts. Check Publish to make a story public; Unpublish returns it to drafts. Public pages are `/blogs` and `/blogs/{slug}`, with search, category filtering and pagination. Homepage culinary stories use the latest published posts.

Body content uses plain paragraphs separated by blank lines. A paragraph starting with `## ` becomes a heading. HTML is escaped. Keep slugs stable after publication; changing one changes its public URL. Cover images use the existing uploads storage and must be JPG/PNG/WebP up to 2 MB. Full articles and approved high-resolution covers remain client-provided. The local aloo-in-biryani story is a short editorial preview linking to the original, not a migration of the full Zappak article.

Run `python tests/blogs.py` against the local preview server on port 8088 to check admin access, CSRF, publication visibility, filtering, escaping and unpublishing. The script removes its own test article afterward.
