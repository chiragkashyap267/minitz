# Minitz

Custom paper-world storefront and a matching Laravel kitchen desk, using the original blue, yellow, orange, lilac and green palette.

The public website is temporarily covered by a clean Coming soon page on all routes. All storefront and dashboard source remains intact. The full application is available on the explicitly enabled local development server. See the temporary mode instructions in DEPLOYMENT.md.

## Included

- Responsive homepage with reduced-motion-aware animation.
- Product catalogue, search, category and price sorting, product pages and a server-priced session cart.
- Recipes, printable cooking notes, brand story, contact and editable placeholder policies.
- Branded admin login and dashboard; product and recipe management with image uploads; banners, headings, social links and delivery settings.
- Preview checkout, customer and order records, fulfilment controls for verified payments, and private order/customer/sales PDFs.
- Razorpay integration code with server-created orders, callback signatures, signed captured-payment webhooks and duplicate-event handling. Live payments are disabled by default.

## Deployment stages

During construction, `index.php`, `index.html` and the hosting rewrite rules show the holding page. Once that temporary gate is removed, the existing read-only-preview and Laravel activation stages apply. No build command is required for the storefront assets.

Follow [DEPLOYMENT.md](DEPLOYMENT.md) for Hostinger setup, runtime directories, MySQL and secure admin creation. Git excludes dependencies; use Composer through SSH or the dependency-inclusive ZIP.

There are no default hosted admin credentials. Environment files, customer records and runtime data are excluded from Git. The catalogue, prices, packaging, recipes and policies are placeholders; default checkout creates preview orders without payment or delivery.

## Stack

Laravel 12 and Blade, MySQL on Hostinger (SQLite locally), custom CSS, small vanilla JavaScript interactions, Dompdf and Razorpay. No paid UI kit or animation library is required.

## Verification

`tests/smoke.py` checks the local storefront, cart pricing, preview ordering, access control, CSRF, admin editing and PDF generation. `tests/payment.php` checks mocked payment events, invalid signatures, mismatched totals/currency and idempotency. Tests target local sample data only. Desktop and 390px mobile layouts were reviewed in the browser.

Merchant sandbox/live credentials, SMTP delivery and production MySQL must still be configured and verified on the hosting account. Live-launch items, including unpaid-stock reservation reconciliation, final taxes, coverage rules and approved policy content, are listed in the deployment guide.
