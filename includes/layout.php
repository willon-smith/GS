<?php
/**
 * Public layout: header and footer.
 *
 *   render_header(['title' => 'Shop', 'description' => '...', 'hero' => true, 'body_class' => 'page-shop', 'jsonld' => [...]])
 *   ... page content ...
 *   render_footer();
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/svg.php';

function nav_items(): array
{
    return [
        ['label' => 'Shop',        'href' => route('shop'),               'match' => 'shop'],
        ['label' => 'The roast',   'href' => route('home') . '#roast',    'match' => null],
        ['label' => 'Stewardship', 'href' => route('home') . '#steward',  'match' => null],
        ['label' => 'About',       'href' => route('about'),              'match' => 'about'],
        ['label' => 'Contact',     'href' => route('contact'),            'match' => 'contact'],
    ];
}

function render_header(array $o = []): void
{
    $store   = setting('store_name');
    $title   = isset($o['title']) ? $o['title'] . ' · ' . $store : $store . ' · ' . setting('tagline');
    $desc    = $o['description'] ?? setting('meta_description');
    $hero    = !empty($o['hero']);
    $class   = trim(($o['body_class'] ?? '') . ($hero ? ' has-hero' : ''));
    $page    = current_page();
    $cart    = cart();
    $ogImage = site_url('assets/img/og.png');
    $canon   = site_url(ltrim($_SERVER['REQUEST_URI'] ?? '/', '/'));
    $canon   = strtok($canon, '?') ?: $canon;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#0B1729">
<link rel="canonical" href="<?= e($canon) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($store) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="icon" href="<?= e(url('assets/img/favicon.png')) ?>" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=DM+Sans:opsz,wght@9..40,400..700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
<style>:root{--grain:url("<?= grain_uri() ?>");}</style>
<?php if (!empty($o['jsonld'])): ?>
<script type="application/ld+json"><?= json_encode($o['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org', '@type' => 'Organization',
    'name' => $store, 'url' => site_url(), 'logo' => site_url('assets/img/apple-touch-icon.png'),
    'email' => setting('email'), 'telephone' => setting('phone'),
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => setting('address_line1'), 'addressLocality' => 'Bloemfontein', 'addressCountry' => 'ZA'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="<?= e($class) ?>" data-base="<?= e(BASE_URL) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<div class="pt is-out" aria-hidden="true"></div>
<div class="cursor" aria-hidden="true"></div>
<div class="cursor__ring" aria-hidden="true"></div>

<?php if (!$hero && setting('announcement') !== ''): ?>
<div class="announce"><?= e(setting('announcement')) ?></div>
<?php endif; ?>

<header class="site-header" id="site-header">
  <div class="container header__inner">
    <a class="brand" href="<?= e(route('home')) ?>" aria-label="<?= e($store) ?> home">
      <?= svg_mark(40, 'currentColor', $hero ? '#0B1729' : '#F4ECE1') ?>
      <span class="wordmark"><span class="wordmark__top">Commodities</span><span class="wordmark__main">Good Steward</span></span>
    </a>
    <nav class="nav" aria-label="Primary">
      <?php foreach (nav_items() as $item): ?>
        <a class="nav__link<?= ($item['match'] !== null && $item['match'] === $page) ? ' is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="header__actions">
      <button class="icon-btn cart-btn" type="button" id="cart-open" aria-label="Open your bag" aria-controls="cart-drawer" aria-expanded="false">
        <?= icon('bag', 20) ?>
        <span class="cart-btn__label">Bag</span>
        <span class="cart-btn__count" id="cart-count" data-count="<?= (int) $cart['count'] ?>"><?= (int) $cart['count'] ?></span>
      </button>
      <button class="icon-btn nav-toggle" type="button" id="nav-open" aria-label="Open menu" aria-controls="mobile-nav" aria-expanded="false"><?= icon('menu', 24) ?></button>
    </div>
  </div>
</header>

<div class="mobile-nav" id="mobile-nav" aria-hidden="true">
  <button class="mobile-nav__close" type="button" id="nav-close" aria-label="Close menu"><?= icon('close', 22) ?></button>
  <ul class="mobile-nav__list">
    <?php foreach (nav_items() as $i => $item): ?>
      <li><a class="mobile-nav__link" style="--i:<?= $i ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a></li>
    <?php endforeach; ?>
    <li><a class="mobile-nav__link" style="--i:5" href="<?= e(route('cart')) ?>">Your <em>bag</em></a></li>
  </ul>
  <div class="mobile-nav__meta"><?= e(setting('email')) ?> · <?= e(setting('phone')) ?></div>
</div>

<div class="backdrop" id="backdrop"></div>
<aside class="drawer" id="cart-drawer" aria-label="Your bag" aria-hidden="true">
  <div class="drawer__head">
    <h3>Your bag <span id="drawer-count"></span></h3>
    <button class="drawer__close" type="button" id="cart-close" aria-label="Close bag"><?= icon('close', 20) ?></button>
  </div>
  <div class="drawer__items" id="drawer-items"></div>
  <div class="drawer__foot" id="drawer-foot"></div>
</aside>
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<main id="main">
<?php
}

function render_footer(): void
{
    $store = setting('store_name');
    ?>
</main>

<footer class="site-footer">
  <div class="container footer__top">
    <div class="footer__grid">
      <div class="footer__brand">
        <a class="brand" href="<?= e(route('home')) ?>">
          <?= svg_mark(40, '#F4ECE1', '#0B1729') ?>
          <span class="wordmark" style="color:var(--cream)"><span class="wordmark__top">Commodities</span><span class="wordmark__main">Good Steward</span></span>
        </a>
        <p><?= e(setting('tagline')) ?> Roasted in small batches in Bloemfontein and delivered across South Africa.</p>
        <div class="social">
          <?php if (setting('instagram') !== ''): ?><a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('instagram', 18) ?></a><?php endif; ?>
          <?php if (setting('facebook') !== ''): ?><a href="<?= e(setting('facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icon('facebook', 18) ?></a><?php endif; ?>
          <?php if (setting('whatsapp') !== ''): ?><a href="https://wa.me/<?= e(setting('whatsapp')) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('whatsapp', 18) ?></a><?php endif; ?>
        </div>
      </div>
      <div class="footer__col">
        <h4>Shop</h4>
        <ul>
          <?php foreach (products_active() as $p): ?>
            <li><a href="<?= e(route('product', $p['slug'])) ?>"><?= e($p['name']) ?> · <?= e($p['size_label']) ?></a></li>
          <?php endforeach; ?>
          <li><a href="<?= e(route('cart')) ?>">Your bag</a></li>
        </ul>
      </div>
      <div class="footer__col">
        <h4>Company</h4>
        <ul>
          <li><a href="<?= e(route('about')) ?>">Our story</a></li>
          <li><a href="<?= e(route('home')) ?>#steward">Stewardship</a></li>
          <li><a href="<?= e(route('home')) ?>#brew">Brew guide</a></li>
          <li><a href="<?= e(route('faq')) ?>">Questions</a></li>
          <li><a href="<?= e(route('contact')) ?>">Contact</a></li>
        </ul>
      </div>
      <div class="footer__col">
        <h4>Find us</h4>
        <ul>
          <li><?= e(setting('address_line1')) ?><br><?= e(setting('address_line2')) ?></li>
          <li><a href="mailto:<?= e(setting('email')) ?>"><?= icon('mail', 16) ?> <?= e(setting('email')) ?></a></li>
          <li><a href="tel:<?= e(preg_replace('/\s+/', '', setting('phone'))) ?>"><?= icon('phone', 16) ?> <?= e(setting('phone')) ?></a></li>
          <li><?= e(setting('hours')) ?></li>
        </ul>
      </div>
    </div>
    <div class="footer__wordmark" aria-hidden="true">Good Steward</div>
  </div>
  <div class="container footer__bottom">
    <span>&copy; <?= date('Y') ?> <?= e($store) ?>. All rights reserved.</span>
    <span><a href="<?= e(route('faq')) ?>#delivery">Delivery &amp; returns</a> · <a href="<?= e(route('faq')) ?>#privacy">Privacy</a> · <a href="<?= e(url('admin/')) ?>">Admin</a></span>
  </div>
</footer>

<script>
window.CGS = {
  base: <?= json_encode(BASE_URL) ?>,
  api: <?= json_encode(url('api/')) ?>,
  csrf: <?= json_encode(csrf_token()) ?>,
  currency: <?= json_encode(setting('currency_symbol', 'R')) ?>
};
</script>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
<?php
}
