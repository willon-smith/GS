<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$products = products_active();
$featured = featured_product();
$grinds   = grinds_active();
$quotes   = testimonials_active();
$notes    = $featured ? notes_list($featured['tasting_notes']) : [];

$heroTitle = setting('hero_title', 'Coffee, stewarded from farm to cup.');
// Split the title into words for the reveal; the last word is set in italic.
$words = preg_split('/\s+/', trim($heroTitle)) ?: [];
$titleHtml = '';
foreach ($words as $i => $w) {
    $isLast = $i === count($words) - 1;
    $titleHtml .= '<span class="w"><span style="--i:' . $i . '">' . ($isLast ? '<em>' . e($w) . '</em>' : e($w)) . '</span></span> ';
}

$jsonld = null;
if ($featured) {
    $jsonld = [
        '@context' => 'https://schema.org', '@type' => 'Product',
        'name' => $featured['name'], 'description' => strtok($featured['description'], "\n"),
        'brand' => ['@type' => 'Brand', 'name' => setting('store_name')],
        'offers' => array_map(fn($p) => [
            '@type' => 'Offer', 'price' => number_format($p['price_cents'] / 100, 2, '.', ''), 'priceCurrency' => 'ZAR',
            'availability' => 'https://schema.org/InStock', 'url' => site_url(ltrim(route('product', $p['slug']), '/')),
            'name' => $p['name'] . ' ' . $p['size_label'],
        ], $products),
    ];
}

render_header(['hero' => true, 'body_class' => 'page-home', 'jsonld' => $jsonld]);

$beans = [
    ['l' => '6%',  't' => '18%', 's' => 26, 'o' => .28, 't2' => 15, 'r' => -20, 'dl' => 0],
    ['l' => '14%', 't' => '68%', 's' => 34, 'o' => .22, 't2' => 18, 'r' => 30,  'dl' => 2],
    ['l' => '46%', 't' => '80%', 's' => 22, 'o' => .3,  't2' => 13, 'r' => 10,  'dl' => 4],
    ['l' => '58%', 't' => '12%', 's' => 30, 'o' => .18, 't2' => 17, 'r' => -50, 'dl' => 1],
    ['l' => '88%', 't' => '78%', 's' => 40, 'o' => .2,  't2' => 20, 'r' => 45,  'dl' => 3],
    ['l' => '93%', 't' => '22%', 's' => 24, 'o' => .26, 't2' => 14, 'r' => 15,  'dl' => 5],
    ['l' => '72%', 't' => '92%', 's' => 18, 'o' => .3,  't2' => 12, 'r' => -35, 'dl' => 2.5],
];
?>

<section class="hero" id="top">
  <div class="hero__bg"></div>
  <svg class="hero__rings" viewBox="0 0 800 800" aria-hidden="true">
    <circle cx="400" cy="400" r="160"/><circle cx="400" cy="400" r="240"/><circle cx="400" cy="400" r="320"/><circle cx="400" cy="400" r="399"/>
  </svg>
  <div class="hero__beans" aria-hidden="true">
    <?php foreach ($beans as $b): ?>
      <?= svg_bean('', '#5C3A25', 'left:' . $b['l'] . ';top:' . $b['t'] . ';--s:' . $b['s'] . 'px;--o:' . $b['o'] . ';--t:' . $b['t2'] . 's;--r:' . $b['r'] . 'deg;--dl:' . $b['dl'] . 's') ?>
    <?php endforeach; ?>
  </div>

  <div class="container hero__inner">
    <div class="hero__copy">
      <span class="eyebrow"><?= e(setting('hero_eyebrow')) ?></span>
      <h1 class="hero__title split"><?= $titleHtml ?></h1>
      <p class="hero__sub"><?= e(setting('hero_subtitle')) ?></p>
      <div class="hero__cta">
        <a class="btn btn--copper btn--lg btn-magnetic" href="<?= e(route('shop')) ?>">Shop the roast <?= icon('arrow', 18) ?></a>
        <a class="btn btn--outline btn--lg btn-magnetic" href="#steward">How we source</a>
      </div>
      <div class="hero__meta">
        <span><?= icon('fire', 16) ?> Roasted every <?= e(setting('roast_day', 'Thursday')) ?></span>
        <span><?= icon('truck', 16) ?> <?= e(setting('delivery_estimate', '2 to 4 days nationwide')) ?></span>
        <span><?= icon('leaf', 16) ?> Paid fairly at origin</span>
      </div>
    </div>

    <div class="hero__stage" id="hero-stage">
      <div class="hero__glow"></div>
      <?php if ($featured): ?>
        <div class="hero__bag" id="hero-bag">
          <?php if ($img = product_image_url($featured)): ?>
            <img src="<?= e($img) ?>" alt="<?= e($featured['name']) ?>" style="border-radius:24px;animation:floatY 7s ease-in-out infinite 1.7s">
          <?php else: ?>
            <?= svg_bag($featured, 'hero') ?>
          <?php endif; ?>
        </div>
        <?php foreach (array_slice($notes, 0, 3) as $i => $n): ?>
          <span class="chip chip--<?= $i + 1 ?> chip--float"><span class="chip__dot"></span><?= e($n) ?></span>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero__scroll"><span></span>Scroll</div>
</section>

<div class="marquee" aria-hidden="true">
  <div class="marquee__track">
    <?php $items = array_map('trim', explode('·', setting('announcement'))); $items = array_filter($items); ?>
    <?php for ($r = 0; $r < 2; $r++): foreach ($items as $it): ?>
      <span class="marquee__item"><?= e($it) ?></span>
    <?php endforeach; endfor; ?>
    <?php if (!$items): for ($r = 0; $r < 6; $r++): ?><span class="marquee__item"><?= e(setting('tagline')) ?></span><?php endfor; endif; ?>
  </div>
</div>

<?php if ($featured): ?>
<section class="section section--foam" id="roast">
  <div class="container profile__grid">
    <div class="profile__visual" data-reveal="left">
      <svg class="profile__ring" viewBox="0 0 400 400" aria-hidden="true">
        <defs><path id="ringPath" d="M200,200 m-170,0 a170,170 0 1,1 340,0 a170,170 0 1,1 -340,0"/></defs>
        <text><textPath href="#ringPath" startOffset="0">Roasted in Bloemfontein · Single origin · Small batch · Stewarded from farm to cup · Roasted in Bloemfontein · Single origin · Small batch ·</textPath></text>
      </svg>
      <?php if ($img = product_image_url($featured)): ?>
        <img src="<?= e($img) ?>" alt="<?= e($featured['name']) ?>" style="width:min(360px,100%);border-radius:24px;position:relative">
      <?php else: ?>
        <?= svg_bag($featured, 'profile') ?>
      <?php endif; ?>
    </div>
    <div data-reveal>
      <span class="eyebrow">The roast</span>
      <h2 class="section__title"><?= e($featured['name']) ?>, <em><?= e($featured['origin']) ?></em></h2>
      <p class="section__lede"><?= e(strtok($featured['description'], "\n")) ?></p>
      <?php if ($notes): ?>
      <div class="notes">
        <?php foreach ($notes as $n): ?><span class="note"><?= e($n) ?></span><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="meters" data-reveal>
        <?php foreach (['acidity' => 'Acidity', 'body' => 'Body', 'sweetness' => 'Sweetness'] as $k => $label): $v = max(1, min(5, (int) $featured[$k])); ?>
          <div class="meter">
            <span class="meter__label"><?= $label ?></span>
            <span class="meter__bar"><span class="meter__fill" style="--v:<?= $v * 20 ?>%"></span></span>
            <span class="meter__val"><?= $v ?>/5</span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="roast-scale" data-reveal>
        <div class="roast-scale__bar"><span class="roast-scale__marker" style="--pos:<?= (max(1, min(5, (int) $featured['roast_level'])) - 0.5) * 20 ?>%"></span></div>
        <div class="roast-scale__labels"><span>Light</span><span>Medium</span><span>Dark</span></div>
      </div>
      <div class="profile__facts">
        <div class="fact"><div class="fact__label">Region</div><div class="fact__value"><?= e($featured['region']) ?></div></div>
        <div class="fact"><div class="fact__label">Process</div><div class="fact__value"><?= e($featured['process']) ?></div></div>
        <div class="fact"><div class="fact__label">Altitude</div><div class="fact__value"><?= e($featured['altitude']) ?></div></div>
        <div class="fact"><div class="fact__label">Varietal</div><div class="fact__value"><?= e($featured['varietal']) ?></div></div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--cream" id="shop">
  <div class="container">
    <?php
    $groups = array_unique(array_column($products, 'group_key'));
    $numWords = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];
    $oneCoffee = count($groups) === 1 && count($products) > 1;
    $countWord = $numWords[count($products)] ?? (string) count($products);
    ?>
    <div class="section__head section__head--center" data-reveal>
      <span class="eyebrow"><?= $oneCoffee ? ucfirst($countWord) . ' sizes' : 'The range' ?></span>
      <h2 class="section__title"><?= $oneCoffee ? 'One standard, <em>' . e($countWord) . ' bags</em>' : 'Chosen one lot <em>at a time</em>' ?></h2>
      <p class="section__lede"><?= $oneCoffee ? 'Same coffee, same roast day, same care. Choose the size that suits your kitchen, then tell us how you brew and we grind it to match.' : 'Every coffee here is bought as a single lot and roasted on the same day. Pick a bag, then tell us how you brew and we grind it to match.' ?></p>
    </div>
    <div class="products" data-reveal-stagger>
      <?php foreach ($products as $i => $p): ?>
        <?php include __DIR__ . '/includes/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--foam" id="grind">
  <div class="container">
    <div class="section__head" data-reveal>
      <span class="eyebrow">Ground for your brewer</span>
      <h2 class="section__title">Whole bean, or ground <em>the way you brew</em></h2>
      <p class="section__lede">We grind within minutes of packing. Whole bean stays freshest for longest, so if you own a grinder, choose beans. If not, pick the option that matches your brewer and we do the rest.</p>
    </div>
    <div class="grinds" data-reveal-stagger>
      <?php $sizes = ['whole-bean' => [11, 11, 11], 'coarse' => [8, 8, 8], 'medium' => [6, 6, 6, 6], 'fine' => [4, 4, 4, 4, 4], 'espresso' => [2, 2, 2, 2, 2, 2, 2]]; ?>
      <?php foreach ($grinds as $i => $g): ?>
        <div class="grind-card" style="--i:<?= $i ?>">
          <div class="grind-card__icon"><?= icon($g['icon'], 26) ?></div>
          <h3 class="grind-card__title"><?= e($g['name']) ?></h3>
          <div class="grind-card__for"><?= e($g['brewers']) ?></div>
          <p class="grind-card__desc"><?= e($g['description']) ?></p>
          <div class="grind-texture" aria-hidden="true">
            <?php foreach ($sizes[$g['key']] ?? [6, 6, 6] as $d): ?><i style="--g:<?= $d ?>px"></i><?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--dark grain steward" id="steward">
  <div class="container">
    <div class="steward__grid">
      <div data-reveal>
        <span class="eyebrow">Stewardship</span>
        <h2 class="section__title">What it means to be a <em>good steward</em> of coffee</h2>
        <p class="steward__intro mt-3"><?= e(setting('stewardship_intro')) ?></p>
      </div>
      <div class="pillars" data-reveal-stagger>
        <div class="pillar">
          <div class="pillar__icon"><?= icon('leaf', 22) ?></div>
          <div><h3 class="pillar__title">Paid properly at origin</h3><p>We buy directly from the washing station and pay a price the farmer can plan a season around. What we pay is printed on the bag.</p></div>
        </div>
        <div class="pillar">
          <div class="pillar__icon"><?= icon('fire', 22) ?></div>
          <div><h3 class="pillar__title">Roasted to order</h3><p>One roast day a week. Your bag is roasted after you order it, so it arrives days old, never months.</p></div>
        </div>
        <div class="pillar">
          <div class="pillar__icon"><?= icon('eye', 22) ?></div>
          <div><h3 class="pillar__title">Fully traceable</h3><p>Every bag names the region, the process and the harvest. If we cannot tell you where a coffee came from, we do not sell it.</p></div>
        </div>
        <div class="pillar">
          <div class="pillar__icon"><?= icon('box', 22) ?></div>
          <div><h3 class="pillar__title">Nothing wasted</h3><p>Chaff goes to compost, bags are recyclable, and we roast to demand so no coffee sits and fades on a shelf.</p></div>
        </div>
      </div>
    </div>
    <div class="stats" data-reveal-stagger>
      <div class="stat"><div class="stat__num" data-count="1">0</div><div class="stat__label">origin, chosen on merit</div></div>
      <div class="stat"><div class="stat__num" data-count="<?= count($products) ?>">0</div><div class="stat__label">bag sizes, one standard</div></div>
      <div class="stat"><div class="stat__num" data-count="<?= count($grinds) ?>">0</div><div class="stat__label">grind options, done to order</div></div>
      <div class="stat"><div class="stat__num" data-count="0">0</div><div class="stat__label">shortcuts, from farm to cup</div></div>
    </div>
  </div>
</section>

<section class="section section--cream" id="journey">
  <div class="container">
    <div class="section__head" data-reveal>
      <span class="eyebrow">The journey</span>
      <h2 class="section__title">From the hills of <em><?= e($featured ? preg_replace('/,.*$/', '', $featured['region']) : 'origin') ?></em> to your kitchen</h2>
    </div>
    <div class="journey" data-reveal>
      <div class="journey__line"></div>
      <?php
      $steps = [
          ['Harvest', 'Cherries are picked ripe, by hand, over several passes through the season.'],
          ['Washed and dried', 'Pulped, fermented and sun dried on raised beds until the moisture is right.'],
          ['Shipped green', 'Sealed in grain-pro liners and shipped to South Africa in its raw, green state.'],
          ['Roasted in Bloemfontein', 'Small batches, roasted every ' . setting('roast_day', 'Thursday') . ' to a profile we cup and approve.'],
          ['Ground to order', 'Whole bean or ground for your brewer minutes before the bag is sealed.'],
          ['At your door', setting('delivery_estimate', '2 to 4 working days nationwide') . ', or collect from the roastery.'],
      ];
      foreach ($steps as $i => $s): ?>
        <div class="journey__step" style="--i:<?= $i ?>">
          <div class="journey__num"><?= $i + 1 ?></div>
          <h3 class="journey__title"><?= e($s[0]) ?></h3>
          <p><?= e($s[1]) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--foam" id="brew">
  <div class="container brew__grid">
    <div data-reveal="left">
      <span class="eyebrow">Brew guide</span>
      <h2 class="section__title">How much coffee <em>do you need?</em></h2>
      <p class="section__lede">Pick your brewer and slide to the number of cups. We do the maths for the coffee, the water and the grind, so every cup tastes the way we tasted it at the roastery.</p>
      <p class="muted mt-3" style="font-size:var(--text-sm)">A cup here is 250 ml. Ratios are starting points; adjust to taste in small steps and keep a note of what you liked.</p>
      <?= svg_steam('brew__steam') ?>
    </div>
    <div class="brew__card" data-reveal="right" id="brew-calc">
      <div class="brew__methods" role="tablist" aria-label="Brewer">
        <button class="brew__method is-active" type="button" data-ratio="16" data-grind="Medium" data-time="3 to 4 min"><?= icon('pourover', 16) ?> Pour-over</button>
        <button class="brew__method" type="button" data-ratio="15" data-grind="Coarse" data-time="4 min steep"><?= icon('press', 16) ?> French press</button>
        <button class="brew__method" type="button" data-ratio="13" data-grind="Fine" data-time="1.5 to 2 min"><?= icon('cup', 16) ?> AeroPress</button>
        <button class="brew__method" type="button" data-ratio="10" data-grind="Fine" data-time="until it gurgles"><?= icon('moka', 16) ?> Moka pot</button>
        <button class="brew__method" type="button" data-ratio="2" data-grind="Espresso" data-time="25 to 30 s" data-espresso="1"><?= icon('espresso', 16) ?> Espresso</button>
      </div>
      <div class="brew__cups"><span>Cups</span><strong id="brew-cups">2</strong></div>
      <input class="range" type="range" id="brew-range" min="1" max="12" value="2" step="1" aria-label="Number of cups">
      <div class="brew__out">
        <div class="brew__val"><strong id="brew-coffee">31 g</strong><span>Coffee</span></div>
        <div class="brew__val"><strong id="brew-water">500 ml</strong><span>Water</span></div>
        <div class="brew__val"><strong id="brew-grind">Medium</strong><span>Grind</span></div>
      </div>
      <p class="brew__tip" id="brew-tip">Water just off the boil, about 94 °C. Brew time 3 to 4 min.</p>
    </div>
  </div>
</section>

<?php if ($quotes): ?>
<section class="section section--cream" id="words">
  <div class="container">
    <div class="section__head section__head--center" data-reveal>
      <span class="eyebrow">From the kitchen table</span>
      <h2 class="section__title">What people say <em>after the first bag</em></h2>
    </div>
    <div class="testimonials" data-reveal-stagger>
      <?php foreach ($quotes as $i => $t): ?>
        <figure class="testimonial" style="--i:<?= $i ?>">
          <span class="testimonial__quote" aria-hidden="true">&rdquo;</span>
          <div class="stars" aria-label="<?= (int) $t['rating'] ?> out of 5 stars"><?php for ($s = 0; $s < (int) $t['rating']; $s++) echo icon('star', 16); ?></div>
          <blockquote><p><?= e($t['quote']) ?></p></blockquote>
          <figcaption class="testimonial__who">
            <span class="avatar"><?= e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?></span>
            <div><strong><?= e($t['name']) ?></strong><br><span><?= e($t['location']) ?></span></div>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--tight newsletter grain">
  <div class="container newsletter__inner">
    <div data-reveal>
      <span class="eyebrow">Stay close to the roast</span>
      <h2 class="section__title">First to hear about <em>new harvests</em></h2>
      <p class="section__lede">One email when a new lot lands, one when it sells out. Nothing in between.</p>
    </div>
    <div data-reveal="right">
      <form class="newsletter__form" id="newsletter-form" action="<?= e(url('api/newsletter.php')) ?>" method="post">
        <input type="email" name="email" placeholder="Your email address" required aria-label="Email address">
        <button class="btn btn--copper" type="submit">Subscribe <?= icon('arrow', 16) ?></button>
      </form>
      <div class="newsletter__msg" id="newsletter-msg"></div>
    </div>
  </div>
</section>

<?php render_footer(); ?>
