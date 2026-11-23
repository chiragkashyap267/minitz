# Minitz

Custom Minitz storefront based on the supplied paper-world design.

## Deploy this initial frontend on Hostinger

- Website type: Custom PHP/HTML.
- Git repository: chiragkashyap267/minitz.
- Branch: main.
- Deploy the repository contents into the site's empty public_html directory. index.html is the entry file at the repository root.
- No build command, Node server or paid library is required for this frontend.
- Use a temporary domain for review before replacing an existing live site.

## Current status

The reference homepage, original palette, recipes, illustrations and preview interactions are included. Images are separate cacheable files. Checkout is still a preview; no payment is collected. The custom admin dashboard, database, real cart/orders, Razorpay verification and PDF exports are planned and are not implemented in this initial deployment.

## Planned application

Laravel + Blade, compiled Tailwind CSS, Alpine.js where needed, optional GSAP effects, Hostinger MySQL/storage, Razorpay and private PDF reports. Deployment configuration will change when the Laravel application is introduced; only its public directory will be web-accessible.

Never commit live credentials, .env files, customer records or private reports. Client-supplied artwork remains subject to its original ownership and permissions.
