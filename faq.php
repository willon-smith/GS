<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$fee = money(setting_int('shipping_fee_cents', 7900));
$free = money(setting_int('free_shipping_threshold_cents', 50000));
$grinds = grinds_active();

$groups = [
    'delivery' => ['Orders and delivery', [
        ['When will my coffee arrive?', 'We roast every ' . setting('roast_day', 'Thursday') . '. ' . setting('dispatch_note') . ' Courier delivery takes ' . setting('delivery_estimate') . '. You receive a tracking number by email the day it ships.'],
        ['How much is delivery?', 'Courier delivery is ' . $fee . ' anywhere in South Africa and free on orders over ' . $free . '.' . (setting('collection_enabled') === '1' ? ' Collection from the roastery in Bloemfontein is free.' : '')],
        ['Can I collect my order?', setting('collection_enabled') === '1' ? setting('collection_note') : 'Not at the moment; we deliver by courier nationwide.'],
        ['Can I change or cancel an order?', 'Yes, up until it is roasted. Email us with your order number and we sort it out. Once a bag has been roasted and ground to your choice we cannot resell it, so changes after roast day are not possible.'],
        ['What if something arrives damaged?', 'Email a photo within 7 days and we send a replacement with the next roast. No forms, no fuss.'],
    ]],
    'coffee' => ['The coffee and grinding', [
        ['Which grind should I choose?', implode(' ', array_map(fn($g) => $g['name'] . ' for ' . strtolower($g['brewers']) . '.', $grinds)) . ' If you own a grinder, choose whole bean; it stays fresh for weeks longer than ground coffee.'],
        ['How long does it stay fresh?', 'Whole bean is at its best from one week to about a month off roast, and still good for two months if the bag stays sealed. Ground coffee is best within two to three weeks. The roast date is printed on every bag.'],
        ['How should I store it?', 'In the bag it came in, sealed, in a cupboard away from light and heat. Not in the fridge, where it picks up moisture and odours.'],
        ['Is it really the same coffee in all three sizes?', 'Yes. Same lot, same roast profile, same roast day. The only difference is the bag. Bigger bags cost less per cup because there is less packaging and courier cost per gram.'],
        ['Why only one coffee?', 'Because we would rather do one thing properly. We buy one exceptional lot at a time and put everything into it. When we find another that deserves a place beside it, you will see it here.'],
    ]],
    'payment' => ['Payment', [
        ['How can I pay?', (payfast_enabled() ? 'By card or Instant EFT through PayFast, or ' : 'By ') . 'manual EFT. With manual EFT we roast once your payment reflects, usually within 1 to 2 working days.'],
        ['Is my card information safe?', 'We never see or store card details. PayFast handles the payment on their secure page and sends us only a confirmation.'],
    ]],
    'privacy' => ['Privacy', [
        ['What do you do with my details?', 'We use your name, address, email and phone number to fulfil and deliver your order and to send you updates about it. We do not sell or share your information with anyone except the courier, who needs your address and phone number to deliver.'],
        ['Do you send marketing email?', 'Only if you subscribe, and then only when a new harvest lands or sells out. Every email has an unsubscribe link, and one click removes you.'],
    ]],
];

render_header(['title' => 'Questions', 'body_class' => 'page-faq', 'description' => 'Delivery times, grind choices, freshness, payment and privacy: the answers to what people ask us most.']);

$jsonld = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(route('home')) ?>">Home</a> <?= icon('chevron') ?> <span>Questions</span></nav>
    <span class="eyebrow">Questions</span>
    <h1 class="page-hero__title">Things people <em>ask us</em></h1>
    <p class="page-hero__lede">If your question is not here, <a href="<?= e(route('contact')) ?>" style="text-decoration:underline">send it over</a> and a person will answer.</p>
  </div>
</section>

<section class="section section--foam faq">
  <div class="container--narrow">
    <?php foreach ($groups as $id => [$title, $qs]): ?>
      <div class="faq__group" id="<?= e($id) ?>" data-reveal>
        <h2><?= e($title) ?></h2>
        <div class="acc">
          <?php foreach ($qs as $i => [$q, $a]): $jsonld['mainEntity'][] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]]; ?>
            <div class="acc__item">
              <button class="acc__btn" type="button" aria-expanded="false"><?= e($q) ?> <?= icon('chevron', 20) ?></button>
              <div class="acc__panel"><div><p><?= e($a) ?></p></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<?php render_footer(); ?>
