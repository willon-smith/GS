# Commodities Good Steward

E-commerce website for a single origin coffee roastery: one coffee, three bag sizes, five grind options, a real cart and checkout, and an admin panel that controls every product, price, grind, testimonial and setting on the site.

Built with PHP 8 and SQLite, plain CSS and vanilla JavaScript. No framework, no build step, no external libraries, and no image files to maintain: the logo, the coffee bags, the beans and the icons are all drawn in SVG from the product data. Upload a product photo in the admin and it replaces the drawn bag.

## Run it locally

Requirements: PHP 8.1 or newer with `pdo_sqlite`, `gd` and `fileinfo` (WAMP, XAMPP, Laragon and the stock PHP installer all have these).

**WAMP**

1. Put the folder at `C:\wamp64\www\Websites\Commodities Good Steward` (or anywhere under `www`).
2. Start WAMP and open `http://localhost/Websites/Commodities%20Good%20Steward/`.

**PHP's built-in server**

```bash
php -S localhost:8765 -t "C:/wamp64/www/Websites/Commodities Good Steward"
```

Then open `http://localhost:8765/`.

The database (`data/cgs.sqlite`) creates and seeds itself on the first request, so there is nothing to import.

**Admin**: `/admin/` with username `admin` and password `stewardship`. Change both under Settings, Account, before the site goes anywhere public.

## What is in it

**Storefront**

- Home page with an animated hero, roast profile (tasting notes, flavour meters, roast scale), the three bags, grind guide, stewardship section with counters, farm to cup journey, an interactive brew calculator, testimonials and a newsletter signup.
- Shop and product pages. On a product page the bag size switches in place (price, image, badge and URL update) and the customer picks a grind and quantity.
- Cart as a slide-out drawer with live totals and a free delivery progress bar, plus a full cart page.
- Checkout with courier or collection, manual EFT or PayFast, server side validation, order confirmation page with a private link, and a confirmation email.
- About, contact (messages are stored in the admin), FAQ with structured data, sitemap, robots, Open Graph image and JSON-LD product markup.
- Motion: word by word hero reveal, mouse parallax, floating tasting note chips, scroll reveals, tilt on product cards, magnetic buttons, custom cursor, page transitions, fly to cart, animated counters and meters. All of it switches off under `prefers-reduced-motion`.

**Admin**

- Dashboard: revenue this month, orders awaiting payment, orders to roast, subscribers, best sellers, low stock.
- Products: add, edit, hide, reorder and delete; price, compare-at price, weight, cups, stock, badge, accent colour, photo upload (resized on the server), coffee profile (origin, region, process, altitude, varietal, roast level, acidity, body, sweetness, tasting notes). Products that share a group key appear as size options of one another.
- Grinds and testimonials: add, edit, hide, reorder.
- Orders: filter by status, search, open an order, move it through awaiting payment, paid, roasting, shipped or ready for collection, completed, cancelled; resend the confirmation email.
- Messages and subscribers (with CSV export).
- Settings: store identity and contact details, homepage copy, delivery fees and free delivery threshold, collection, EFT bank details, PayFast keys and sandbox switch, email, pretty URLs, currency symbol, and your own login.

## Folder layout

```
index.php, shop.php, product.php, cart.php, checkout.php, order.php,
about.php, contact.php, faq.php, 404.php, sitemap.php
admin/            login, dashboard, products, grinds, testimonials, orders, messages, subscribers, settings
api/              cart.php (JSON cart), newsletter.php, payfast-itn.php (payment notifications)
includes/         config.php, db.php (schema + seeding), seed.php (template data), functions.php,
                  layout.php (header/footer), svg.php (all artwork), admin.php, partials/
assets/css/main.css   the whole design system
assets/js/main.js     all front-end behaviour
assets/img/           favicon and Open Graph image
data/                 SQLite database and logs (ignored by git)
uploads/              product photos (ignored by git)
```

## Template data to replace

Everything below is placeholder and is edited in the admin, not in code:

| Where | What |
|-------|------|
| Settings, Store | store name, email, phone, WhatsApp, address, hours, social links |
| Settings, Homepage | hero copy, stewardship paragraph, roast day, dispatch note, the About page story |
| Settings, Delivery | courier fee (R79), free delivery threshold (R500), estimate, collection note |
| Settings, Payments | EFT bank details, PayFast merchant ID, key and passphrase |
| Products | the three Steward's Reserve bags (Ethiopia Yirgacheffe, R165 / R295 / R540) and their tasting notes |
| Testimonials | three placeholder quotes |
| About page | the three team members are placeholders in `about.php` |

## Payments

**Manual EFT** works out of the box: the customer sees the bank details, pays, and you mark the order paid in the admin.

**PayFast** is wired in and ships pointed at the PayFast sandbox with their public test credentials. To go live: put your merchant ID, key and passphrase in Settings, Payments, switch sandbox off, and make sure the site is reachable on a public URL so `api/payfast-itn.php` can receive payment notifications. On localhost the return trip works but the notification cannot arrive, so orders stay "awaiting payment" until you mark them paid.

## Email

`mail_enabled` is off by default so nothing breaks on localhost; every email is written to `data/mail.log` instead. Turn it on in Settings, Email and site, once the server can send mail (or swap `send_mail()` in `includes/functions.php` for an SMTP or API client).

## Pretty URLs

Links are `product.php?slug=...` by default, which works everywhere. Under Apache with `mod_rewrite`, switch on Pretty URLs in Settings to get `/product/stewards-reserve-250g`, `/shop`, `/checkout` and so on; the rules are already in `.htaccess`.

## Going live

1. Change the admin password.
2. Set `APP_ENV` to `production` in `includes/config.local.php` (copy the constants you want to override from `config.php`).
3. Make sure `data/` and `uploads/` are writable by the web server and not served directly (the `.htaccess` files handle this under Apache).
4. Serve over HTTPS; the session cookie is marked secure automatically.
5. Fill in the real PayFast credentials and turn sandbox off.
