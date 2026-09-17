<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

http_response_code(404);
render_header(['title' => 'Page not found', 'body_class' => 'page-404']);
?>
<section class="not-found">
  <div class="container" data-reveal>
    <?= svg_bean('', '#DCC4A6', 'width:64px;margin:0 auto 10px;animation:floatY 5s ease-in-out infinite') ?>
    <h1>404</h1>
    <p>That page has been drunk. Or it never existed. Either way, the coffee is this way.</p>
    <div class="flex" style="justify-content:center">
      <a class="btn" href="<?= e(route('home')) ?>">Back home</a>
      <a class="btn btn--outline" href="<?= e(route('shop')) ?>">Shop the roast <?= icon('arrow', 16) ?></a>
    </div>
  </div>
</section>
<?php render_footer(); ?>
