<?php
/** Product card. Expects $p (product row) and $i (index) in scope. */
$soldOut = (int) $p['stock'] === 0;
$img = product_image_url($p);
?>
<article class="product-card" style="--i:<?= (int) ($i ?? 0) ?>;--accent:<?= e($p['accent']) ?>" data-tilt>
  <a class="product-card__stage" href="<?= e(route('product', $p['slug'])) ?>" aria-label="<?= e($p['name'] . ' ' . $p['size_label']) ?>">
    <?php if ($soldOut): ?>
      <span class="product-card__badge product-card__badge--soldout">Sold out</span>
    <?php elseif ($p['badge'] !== ''): ?>
      <span class="product-card__badge"><?= e($p['badge']) ?></span>
    <?php endif; ?>
    <?php if ($img): ?>
      <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?> <?= e($p['size_label']) ?>" loading="lazy">
    <?php else: ?>
      <?= svg_bag($p, 'card-' . (int) $p['id']) ?>
    <?php endif; ?>
  </a>
  <div class="product-card__body">
    <span class="product-card__size"><?= e($p['size_label']) ?> · about <?= (int) $p['cups'] ?> cups</span>
    <h3 class="product-card__title"><?= e($p['name']) ?></h3>
    <p class="product-card__tag"><?= e($p['tagline']) ?></p>
    <div class="product-card__row">
      <div>
        <?php if ((int) $p['compare_price_cents'] > (int) $p['price_cents']): ?><span class="compare"><?= money((int) $p['compare_price_cents']) ?></span><?php endif; ?>
        <span class="price"><?= money((int) $p['price_cents']) ?></span>
      </div>
      <span class="product-card__per"><?= price_per_100g($p) ?> / 100g</span>
    </div>
    <div class="product-card__cta">
      <a class="btn <?= $soldOut ? 'btn--outline' : '' ?>" href="<?= e(route('product', $p['slug'])) ?>"><?= $soldOut ? 'View' : 'Choose grind' ?> <?= icon('arrow', 16) ?></a>
    </div>
  </div>
</article>
