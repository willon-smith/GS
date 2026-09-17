<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$c = cart();
$flashes = take_flashes();

render_header(['title' => 'Your bag', 'body_class' => 'page-cart']);
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(route('home')) ?>">Home</a> <?= icon('chevron') ?> <span>Your bag</span></nav>
    <h1 class="page-hero__title">Your <em>bag</em></h1>
    <?php if ($c['count'] > 0): ?>
      <p class="page-hero__lede"><?= (int) $c['count'] ?> <?= $c['count'] === 1 ? 'item' : 'items' ?>, roasted after you order and shipped within days.</p>
    <?php endif; ?>
  </div>
</section>

<section class="section section--foam">
  <div class="container">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <?php if (!$c['items']): ?>
      <div class="empty" data-reveal>
        <?= svg_bean() ?>
        <h2>Your bag is empty</h2>
        <p>Three sizes, five grinds, one coffee worth the trouble.</p>
        <a class="btn btn--lg" href="<?= e(route('shop')) ?>">Shop the roast <?= icon('arrow', 18) ?></a>
      </div>
    <?php else: ?>
      <div class="cart-page">
        <div class="cart-table" data-reveal>
          <?php foreach ($c['items'] as $it): $p = $it['product']; ?>
            <div class="cart-line" data-cart-line="<?= e($it['key']) ?>">
              <a class="cart-line__img" href="<?= e(route('product', $p['slug'])) ?>">
                <?php if ($img = product_image_url($p)): ?><img src="<?= e($img) ?>" alt=""><?php else: ?><?= svg_bag($p, 'cart-' . md5($it['key'])) ?><?php endif; ?>
              </a>
              <div>
                <a class="cart-line__title" href="<?= e(route('product', $p['slug'])) ?>"><?= e($p['name']) ?> · <?= e($p['size_label']) ?></a>
                <div class="cart-line__meta"><?= e($it['grind']['name']) ?> · <?= money($it['unit_cents']) ?> each</div>
                <div class="qty" aria-label="Quantity">
                  <button class="qty__btn" type="button" data-cart-qty="-1" data-key="<?= e($it['key']) ?>" aria-label="Decrease"><?= icon('minus', 14) ?></button>
                  <span class="qty__val" data-cart-qty-val><?= (int) $it['qty'] ?></span>
                  <button class="qty__btn" type="button" data-cart-qty="1" data-key="<?= e($it['key']) ?>" aria-label="Increase"><?= icon('plus', 14) ?></button>
                </div>
              </div>
              <div>
                <div class="cart-line__price" data-cart-line-total><?= money($it['line_cents']) ?></div>
                <button class="cart-line__remove" type="button" data-cart-remove="<?= e($it['key']) ?>"><?= icon('trash', 14) ?> Remove</button>
              </div>
            </div>
          <?php endforeach; ?>
          <p class="mt-3"><a class="link" href="<?= e(route('shop')) ?>"><?= icon('arrow', 16, 'flip') ?> Continue shopping</a></p>
        </div>

        <aside class="summary" data-reveal="right" id="cart-summary">
          <h3>Summary</h3>
          <?php if ($c['to_free_cents'] > 0): ?>
            <p class="progress__msg">Add <strong data-cart-tofree><?= money($c['to_free_cents']) ?></strong> more for free delivery.</p>
          <?php else: ?>
            <p class="progress__msg"><strong>Free delivery unlocked.</strong></p>
          <?php endif; ?>
          <div class="progress"><span class="progress__fill" data-cart-progress style="--p:<?= (int) min(100, round($c['subtotal_cents'] / max(1, $c['threshold_cents']) * 100)) ?>%"></span></div>
          <div class="summary__line"><span>Subtotal</span><span data-cart-subtotal><?= money($c['subtotal_cents']) ?></span></div>
          <div class="summary__line"><span>Courier delivery</span><span data-cart-shipping><?= $c['shipping_cents'] === 0 ? 'Free' : money($c['shipping_cents']) ?></span></div>
          <div class="summary__total"><span>Total</span><strong data-cart-total><?= money($c['total_cents']) ?></strong></div>
          <a class="btn btn--lg btn--block" href="<?= e(route('checkout')) ?>">Checkout <?= icon('arrow', 18) ?></a>
          <p class="summary__note"><?= icon('shield', 14) ?> Secure checkout. <?= setting('collection_enabled') === '1' ? 'Collection in Bloemfontein is free.' : '' ?></p>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php render_footer(); ?>
