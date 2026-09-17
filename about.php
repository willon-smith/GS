<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$featured = featured_product();
render_header(['title' => 'Our story', 'body_class' => 'page-about', 'description' => 'Why Commodities Good Steward exists, how we buy, and what stewardship means in a cup of coffee.']);
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(route('home')) ?>">Home</a> <?= icon('chevron') ?> <span>Our story</span></nav>
    <span class="eyebrow">Our story</span>
    <h1 class="page-hero__title">A steward looks after what is <em>not theirs alone</em></h1>
    <p class="page-hero__lede">The farmer's harvest. The land it grew on. The cup you pour in the morning. This is how we came to sell one coffee, and why we do it this way.</p>
  </div>
</section>

<section class="section section--foam">
  <div class="container about__grid">
    <div class="about__visual" data-reveal="left">
      <?php if ($featured): ?>
        <?php if ($img = product_image_url($featured)): ?><img src="<?= e($img) ?>" alt="" style="width:62%;border-radius:20px;position:relative;z-index:1"><?php else: ?><?= svg_bag($featured, 'about') ?><?php endif; ?>
      <?php endif; ?>
      <?= svg_bean('', '#C98B55', 'left:12%;top:16%;--s:34px;--t:13s') ?>
      <?= svg_bean('', '#D8A66A', 'right:14%;top:70%;--s:26px;--t:16s;--dl:2s') ?>
      <?= svg_bean('', '#C98B55', 'left:20%;bottom:10%;--s:20px;--t:11s;--dl:4s') ?>
      <?= svg_bean('', '#DCC4A6', 'right:18%;top:14%;--s:18px;--t:15s;--dl:1s') ?>
    </div>
    <div class="prose" data-reveal>
      <?= paragraphs(setting('about_story')) ?>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section__head" data-reveal>
      <span class="eyebrow">How we work</span>
      <h2 class="section__title">Three rules we <em>do not break</em></h2>
    </div>
    <div class="values" data-reveal-stagger>
      <div class="value"><div class="value__num">01</div><h3>Buy directly, pay properly</h3><p>We buy from the washing station, not from a broker's list. The price we pay is above the certified minimum and it is printed on the bag, because a number you can see is a number you can hold us to.</p></div>
      <div class="value"><div class="value__num">02</div><h3>Roast small, roast often</h3><p>One roast day a week, small batches, cupped before they ship. Coffee is at its best from a week to a month off roast, and every bag we send leaves inside that window.</p></div>
      <div class="value"><div class="value__num">03</div><h3>Say what is in the bag</h3><p>Region, process, altitude, harvest, roast date. If we cannot trace a coffee to the people who grew it, we do not sell it. There are no blends here to hide a poor lot behind a good one.</p></div>
    </div>
  </div>
</section>

<section class="section section--dark grain">
  <div class="container">
    <div class="section__head" data-reveal>
      <span class="eyebrow">The people</span>
      <h2 class="section__title">Small team, <em>Bloemfontein roastery</em></h2>
      <p class="section__lede">Placeholder names for now; swap in the real team from the admin when you are ready.</p>
    </div>
    <div class="team" data-reveal-stagger>
      <div class="member" style="background:rgba(244,236,225,.06);border-color:rgba(244,236,225,.12)"><span class="avatar">HR</span><div><strong>Head roaster</strong><span style="color:rgba(244,236,225,.6)">Profiles every lot and runs Thursday roast day</span></div></div>
      <div class="member" style="background:rgba(244,236,225,.06);border-color:rgba(244,236,225,.12)"><span class="avatar">GB</span><div><strong>Green buyer</strong><span style="color:rgba(244,236,225,.6)">Sources at origin and keeps the pricing honest</span></div></div>
      <div class="member" style="background:rgba(244,236,225,.06);border-color:rgba(244,236,225,.12)"><span class="avatar">OP</span><div><strong>Orders and packing</strong><span style="color:rgba(244,236,225,.6)">Grinds, seals, labels and books the courier</span></div></div>
    </div>
  </div>
</section>

<section class="section section--foam section--tight">
  <div class="container text-center" data-reveal>
    <h2 class="section__title">Taste what <em>stewardship</em> means</h2>
    <p class="section__lede" style="margin-inline:auto">Three sizes, five grinds, roasted after you order.</p>
    <a class="btn btn--lg mt-3" href="<?= e(route('shop')) ?>">Shop the roast <?= icon('arrow', 18) ?></a>
  </div>
</section>

<?php render_footer(); ?>
