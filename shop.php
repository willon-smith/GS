<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$products = products_active();
$grinds   = grinds_active();
$featured = featured_product();
$groups   = array_unique(array_column($products, 'group_key'));
$words    = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten'];
$oneCoffee = count($groups) === 1 && count($products) > 1;
$shopTitle = $oneCoffee
    ? ($words[count($products)] ?? count($products)) . ' sizes. <em>One standard.</em>'
    : 'Every bag, <em>roasted to order.</em>';
$shopLede = $oneCoffee
    ? 'Every bag is the same coffee, roasted on the same day, to the same profile. Pick the size that suits your kitchen, then choose how you would like it ground.'
    : 'Each coffee is bought as a single lot, roasted on the same day and ground to your choice. Pick a bag, then tell us how you brew.';

render_header([
    'title'       => 'Shop',
    'description' => 'Choose your bag size and grind. ' . setting('meta_description'),
    'body_class'  => 'page-shop',
]);
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(route('home')) ?>">Home</a> <?= icon('chevron') ?> <span>Shop</span></nav>
    <span class="eyebrow">The range</span>
    <h1 class="page-hero__title"><?= $shopTitle ?></h1>
    <p class="page-hero__lede"><?= e($shopLede) ?></p>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <?php if (!$products): ?>
      <div class="empty">
        <?= svg_bean() ?>
        <h2>Nothing on the shelf right now</h2>
        <p>We are between roasts. Leave your email on the home page and we will let you know the moment the next lot lands.</p>
        <a class="btn" href="<?= e(route('home')) ?>">Back home</a>
      </div>
    <?php else: ?>
      <div class="products" data-reveal-stagger>
        <?php foreach ($products as $i => $p): ?>
          <?php include __DIR__ . '/includes/partials/product-card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--foam">
  <div class="container">
    <div class="section__head" data-reveal>
      <span class="eyebrow">How to choose</span>
      <h2 class="section__title">Which bag, <em>and which grind?</em></h2>
    </div>
    <div class="values" data-reveal-stagger>
      <div class="value">
        <div class="value__num">01</div>
        <h3>Pick a size</h3>
        <p>A 250g bag makes about 15 cups. If you drink a cup a day, 500g lasts a month and costs less per cup. Households and offices go for the kilo.</p>
      </div>
      <div class="value">
        <div class="value__num">02</div>
        <h3>Tell us how you brew</h3>
        <p><?php foreach ($grinds as $g) { echo e($g['name']) . ' for ' . e(strtolower($g['brewers'])) . '. '; } ?>Not sure? Choose medium; it works in almost everything.</p>
      </div>
      <div class="value">
        <div class="value__num">03</div>
        <h3>We roast, then ship</h3>
        <p><?= e(setting('dispatch_note')) ?> Free delivery on orders over <?= money(setting_int('free_shipping_threshold_cents', 50000)) ?>.</p>
      </div>
    </div>
  </div>
</section>

<?php render_footer(); ?>
