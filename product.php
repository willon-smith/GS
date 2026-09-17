<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$product = $slug !== '' ? product_by_slug($slug) : null;
if (!$product) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$siblings = products_in_group($product['group_key']);
if (!$siblings) {
    $siblings = [$product];
}
$grinds   = grinds_active();
$notes    = notes_list($product['tasting_notes']);
$soldOut  = (int) $product['stock'] === 0;
$defaultGrind = $grinds[0]['key'] ?? '';
foreach ($grinds as $g) {
    if ($g['key'] === 'whole-bean') { $defaultGrind = $g['key']; }
}

$variants = array_map(fn($p) => [
    'id'      => (int) $p['id'],
    'slug'    => $p['slug'],
    'name'    => $p['name'],
    'size'    => $p['size_label'],
    'price'   => money((int) $p['price_cents']),
    'compare' => (int) $p['compare_price_cents'] > (int) $p['price_cents'] ? money((int) $p['compare_price_cents']) : '',
    'per'     => price_per_100g($p),
    'cups'    => (int) $p['cups'],
    'badge'   => $p['badge'],
    'accent'  => $p['accent'],
    'stock'   => (int) $p['stock'],
    'tagline' => $p['tagline'],
    'url'     => route('product', $p['slug']),
], $siblings);

$jsonld = [
    '@context' => 'https://schema.org', '@type' => 'Product',
    'name' => $product['name'] . ' ' . $product['size_label'],
    'description' => strtok($product['description'], "\n"),
    'brand' => ['@type' => 'Brand', 'name' => setting('store_name')],
    'sku' => $product['slug'],
    'offers' => ['@type' => 'Offer', 'price' => number_format($product['price_cents'] / 100, 2, '.', ''), 'priceCurrency' => 'ZAR',
        'availability' => $soldOut ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock', 'url' => site_url(ltrim(route('product', $product['slug']), '/'))],
];

render_header([
    'title'       => $product['name'] . ' ' . $product['size_label'],
    'description' => strtok($product['description'], "\n"),
    'body_class'  => 'page-product',
    'jsonld'      => $jsonld,
]);
?>

<section class="section section--foam" style="padding-top:clamp(2rem,4vw,3.5rem)">
  <div class="container pdp" id="pdp" data-variants='<?= e(json_encode($variants, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' data-current="<?= (int) $product['id'] ?>">
    <div class="pdp__stage" id="pdp-stage" style="--accent:<?= e($product['accent']) ?>" data-reveal="scale">
      <?php foreach ($siblings as $s): ?>
        <div class="pdp__img" data-variant-img="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === (int) $product['id'] ? '' : 'hidden' ?>>
          <?php if ($img = product_image_url($s)): ?>
            <img src="<?= e($img) ?>" alt="<?= e($s['name']) ?> <?= e($s['size_label']) ?>">
          <?php else: ?>
            <?= svg_bag($s, 'pdp-' . (int) $s['id']) ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <span class="pdp__roastdate"><?= icon('fire', 14) ?> Roasted every <?= e(setting('roast_day', 'Thursday')) ?></span>
    </div>

    <div class="pdp__info" data-reveal>
      <nav class="crumbs" aria-label="Breadcrumb">
        <a href="<?= e(route('home')) ?>">Home</a> <?= icon('chevron') ?> <a href="<?= e(route('shop')) ?>">Shop</a> <?= icon('chevron') ?> <span><?= e($product['name']) ?> <span id="crumb-size"><?= e($product['size_label']) ?></span></span>
      </nav>
      <div class="pdp__origin"><?= e($product['origin']) ?><?= $product['region'] !== '' ? ' · ' . e($product['region']) : '' ?></div>
      <h1 class="pdp__title"><?= e($product['name']) ?></h1>
      <p class="pdp__tag" id="pdp-tagline"><?= e($product['tagline']) ?></p>

      <div class="pdp__price">
        <span class="compare" id="pdp-compare" <?= (int) $product['compare_price_cents'] > (int) $product['price_cents'] ? '' : 'hidden' ?>><?= money((int) $product['compare_price_cents']) ?></span>
        <span class="price" id="pdp-price"><?= money((int) $product['price_cents']) ?></span>
        <span class="per"><span id="pdp-per"><?= price_per_100g($product) ?></span> per 100g · about <span id="pdp-cups"><?= (int) $product['cups'] ?></span> cups</span>
      </div>

      <?php if ($notes): ?>
        <div class="notes pdp__notes"><?php foreach ($notes as $n): ?><span class="note"><?= e($n) ?></span><?php endforeach; ?></div>
      <?php endif; ?>

      <form class="options" id="buy-form" method="post" action="<?= e(url('api/cart.php')) ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="redirect" value="1">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="product_id" id="buy-product" value="<?= (int) $product['id'] ?>">

        <?php if (count($siblings) > 1): ?>
        <div>
          <div class="option-label">Bag size <span id="size-hint">Same coffee, three sizes</span></div>
          <div class="pills" role="radiogroup" aria-label="Bag size">
            <?php foreach ($siblings as $s): ?>
              <button class="pill<?= (int) $s['id'] === (int) $product['id'] ? ' is-active' : '' ?>" type="button" data-variant="<?= (int) $s['id'] ?>" role="radio" aria-checked="<?= (int) $s['id'] === (int) $product['id'] ? 'true' : 'false' ?>">
                <?php if ($s['badge'] !== ''): ?><span class="pill__badge"><?= e($s['badge']) ?></span><?php endif; ?>
                <strong><?= e($s['size_label']) ?></strong>
                <span><?= money((int) $s['price_cents']) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div>
          <div class="option-label">Grind <span>Ground minutes before packing</span></div>
          <div class="grind-grid" role="radiogroup" aria-label="Grind">
            <?php foreach ($grinds as $g): ?>
              <label class="grind-opt">
                <input type="radio" name="grind" value="<?= e($g['key']) ?>" <?= $g['key'] === $defaultGrind ? 'checked' : '' ?> required>
                <span class="grind-opt__box">
                  <?= icon($g['icon'], 22) ?>
                  <span class="grind-opt__name"><?= e($g['name']) ?></span>
                  <span class="grind-opt__for"><?= e($g['brewers']) ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="pdp__buy">
          <div class="qty qty--lg" aria-label="Quantity">
            <button class="qty__btn" type="button" data-qty="-1" aria-label="Decrease quantity"><?= icon('minus', 16) ?></button>
            <input type="number" name="qty" id="buy-qty" value="1" min="1" max="50" aria-label="Quantity">
            <button class="qty__btn" type="button" data-qty="1" aria-label="Increase quantity"><?= icon('plus', 16) ?></button>
          </div>
          <button class="btn btn--lg btn--block" type="submit" id="buy-btn" <?= $soldOut ? 'disabled' : '' ?>>
            <?= icon('bag', 18) ?> <span id="buy-label"><?= $soldOut ? 'Sold out' : 'Add to bag' ?></span>
          </button>
        </div>
      </form>

      <div class="pdp__note">
        <?= icon('clock', 20) ?>
        <div><strong>Roasted after you order.</strong> <?= e(setting('dispatch_note')) ?></div>
      </div>
      <div class="trust">
        <span><?= icon('truck', 16) ?> Free delivery over <?= money(setting_int('free_shipping_threshold_cents', 50000)) ?></span>
        <span><?= icon('shield', 16) ?> Secure checkout</span>
        <span><?= icon('leaf', 16) ?> Paid fairly at origin</span>
      </div>

      <div class="acc" id="pdp-acc">
        <div class="acc__item is-open">
          <button class="acc__btn" type="button" aria-expanded="true">About this coffee <?= icon('chevron', 20) ?></button>
          <div class="acc__panel"><div><?= paragraphs($product['description']) ?></div></div>
        </div>
        <div class="acc__item">
          <button class="acc__btn" type="button" aria-expanded="false">Origin and profile <?= icon('chevron', 20) ?></button>
          <div class="acc__panel"><div>
            <ul>
              <li><span><strong>Origin:</strong> <?= e($product['origin']) ?><?= $product['region'] !== '' ? ', ' . e($product['region']) : '' ?></span></li>
              <?php if ($product['process'] !== ''): ?><li><span><strong>Process:</strong> <?= e($product['process']) ?></span></li><?php endif; ?>
              <?php if ($product['altitude'] !== ''): ?><li><span><strong>Altitude:</strong> <?= e($product['altitude']) ?></span></li><?php endif; ?>
              <?php if ($product['varietal'] !== ''): ?><li><span><strong>Varietal:</strong> <?= e($product['varietal']) ?></span></li><?php endif; ?>
              <li><span><strong>Roast:</strong> <?= ['', 'Light', 'Light to medium', 'Medium', 'Medium to dark', 'Dark'][max(1, min(5, (int) $product['roast_level']))] ?></span></li>
              <li><span><strong>Acidity</strong> <?= (int) $product['acidity'] ?>/5 · <strong>Body</strong> <?= (int) $product['body'] ?>/5 · <strong>Sweetness</strong> <?= (int) $product['sweetness'] ?>/5</span></li>
            </ul>
          </div></div>
        </div>
        <div class="acc__item">
          <button class="acc__btn" type="button" aria-expanded="false">How to brew it <?= icon('chevron', 20) ?></button>
          <div class="acc__panel"><div>
            <ul>
              <li><span>Filter and pour-over: 1:16, medium grind, water just off the boil, 3 to 4 minutes.</span></li>
              <li><span>French press: 1:15, coarse grind, 4 minute steep, press slowly.</span></li>
              <li><span>Espresso: 18g in, 36g out, 25 to 30 seconds. Let the beans rest 7 to 10 days off roast for espresso.</span></li>
              <li><span>Store sealed, in the bag, away from light and heat. Not in the fridge.</span></li>
            </ul>
            <p><a class="link" href="<?= e(route('home')) ?>#brew">Open the brew calculator <?= icon('arrow', 14) ?></a></p>
          </div></div>
        </div>
        <div class="acc__item">
          <button class="acc__btn" type="button" aria-expanded="false">Delivery and returns <?= icon('chevron', 20) ?></button>
          <div class="acc__panel"><div>
            <ul>
              <li><span>Courier delivery <?= money(setting_int('shipping_fee_cents', 7900)) ?> nationwide, free over <?= money(setting_int('free_shipping_threshold_cents', 50000)) ?>. <?= e(setting('delivery_estimate')) ?>.</span></li>
              <?php if (setting('collection_enabled') === '1'): ?><li><span>Free collection from the roastery in Bloemfontein.</span></li><?php endif; ?>
              <li><span>If a bag arrives damaged or is not what you ordered, email us within 7 days and we replace it.</span></li>
            </ul>
          </div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (count($siblings) > 1): ?>
<section class="section section--cream section--tight">
  <div class="container">
    <div class="section__head" data-reveal>
      <span class="eyebrow">Also available</span>
      <h2 class="section__title">The same coffee, <em>every size</em></h2>
    </div>
    <div class="products" data-reveal-stagger>
      <?php foreach ($siblings as $i => $p): ?>
        <?php include __DIR__ . '/includes/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php render_footer(); ?>
