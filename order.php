<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$ref = trim((string) ($_GET['ref'] ?? ''));
$key = trim((string) ($_GET['key'] ?? ''));
$order = ($ref !== '' && $key !== '') ? order_by_ref($ref, $key) : null;
if (!$order) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pf = (string) ($_GET['pf'] ?? '');
$isCollection = $order['delivery_method'] === 'collection';
$pending = $order['status'] === 'pending_payment';

render_header(['title' => 'Order ' . $order['ref'], 'body_class' => 'page-order']);
?>

<section class="section section--foam">
  <div class="container">
    <div class="success" data-reveal>
      <?php if ($pf === 'cancel' && $pending): ?>
        <div class="success__check" style="background:var(--error)"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></div>
        <h1>Payment <em>not completed</em></h1>
        <p>No money has moved. Your order is saved, and you can pay whenever you are ready using the button below.</p>
      <?php elseif ($pf === 'return' && $pending): ?>
        <div class="success__check"><svg viewBox="0 0 24 24"><path d="M5 12l5 5L20 7"/></svg></div>
        <h1>Thank you, <em><?= e($order['first_name']) ?></em></h1>
        <p>PayFast has your payment. It usually confirms within a minute; this page updates once it does.</p>
      <?php else: ?>
        <div class="success__check"><svg viewBox="0 0 24 24"><path d="M5 12l5 5L20 7"/></svg></div>
        <h1>Thank you, <em><?= e($order['first_name']) ?></em></h1>
        <p><?= $pending ? 'Your order is saved. Once payment reflects we roast it on the next roast day and send it on its way.' : 'Your order is confirmed and on its way through the roastery.' ?></p>
      <?php endif; ?>
      <div class="ref"><?= e($order['ref']) ?></div>
      <div class="mt-3"><span class="status status--<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span></div>
    </div>

    <div class="order-grid">
      <div class="stack">
        <div class="order-box" data-reveal>
          <h3>Items</h3>
          <?php foreach ($order['items'] as $it): ?>
            <div class="item-line">
              <div><?= e($it['product_name']) ?> · <?= e($it['size_label']) ?><small><?= e($it['grind_name']) ?> · <?= money((int) $it['unit_cents']) ?> each</small></div>
              <div>× <?= (int) $it['qty'] ?></div>
              <strong><?= money((int) $it['line_cents']) ?></strong>
            </div>
          <?php endforeach; ?>
          <div class="summary__line"><span>Subtotal</span><span><?= money((int) $order['subtotal_cents']) ?></span></div>
          <div class="summary__line"><span><?= $isCollection ? 'Collection' : 'Courier delivery' ?></span><span><?= (int) $order['shipping_cents'] === 0 ? 'Free' : money((int) $order['shipping_cents']) ?></span></div>
          <div class="summary__total"><span>Total</span><strong><?= money((int) $order['total_cents']) ?></strong></div>
        </div>

        <?php if ($pending && $order['payment_method'] === 'eft'): ?>
        <div class="order-box" data-reveal>
          <h3>Pay by EFT</h3>
          <p class="muted" style="font-size:var(--text-sm)"><?= e(setting('eft_note')) ?></p>
          <dl>
            <dt>Bank</dt><dd><?= e(setting('eft_bank_name')) ?></dd>
            <dt>Account name</dt><dd><?= e(setting('eft_account_name')) ?></dd>
            <dt>Account number</dt><dd><?= e(setting('eft_account_number')) ?></dd>
            <dt>Branch code</dt><dd><?= e(setting('eft_branch_code')) ?></dd>
            <dt>Reference</dt><dd><strong><?= e($order['ref']) ?></strong></dd>
            <dt>Amount</dt><dd><strong><?= money((int) $order['total_cents']) ?></strong></dd>
          </dl>
        </div>
        <?php elseif ($pending && $order['payment_method'] === 'payfast' && payfast_enabled()): ?>
        <div class="order-box" data-reveal>
          <h3>Pay with PayFast</h3>
          <p class="muted" style="font-size:var(--text-sm)">Card, Instant EFT, SnapScan and more. You are taken to PayFast and brought straight back here.</p>
          <form action="<?= e(payfast_process_url()) ?>" method="post">
            <?php foreach (payfast_fields($order) as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>"><?php endforeach; ?>
            <button class="btn btn--lg" type="submit">Pay <?= money((int) $order['total_cents']) ?> now <?= icon('arrow', 18) ?></button>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <div class="stack">
        <div class="order-box" data-reveal="right">
          <h3><?= $isCollection ? 'Collection' : 'Delivery' ?></h3>
          <?php if ($isCollection): ?>
            <address><?= e($order['first_name'] . ' ' . $order['last_name']) ?><br><?= e(setting('address_line1')) ?><br><?= e(setting('address_line2')) ?><br><?= e(setting('hours')) ?></address>
            <p class="muted mt-2" style="font-size:var(--text-sm);margin:0"><?= e(setting('collection_note')) ?></p>
          <?php else: ?>
            <address><?= e($order['first_name'] . ' ' . $order['last_name']) ?><br><?= e($order['address1']) ?><br><?= $order['address2'] !== '' ? e($order['address2']) . '<br>' : '' ?><?= e($order['suburb']) ?>, <?= e($order['city']) ?><br><?= e($order['province']) ?>, <?= e($order['postcode']) ?><br><?= e($order['phone']) ?></address>
          <?php endif; ?>
        </div>
        <div class="order-box" data-reveal="right">
          <h3>What happens next</h3>
          <ol class="steps">
            <?php if ($pending): ?><li>We receive your payment<?= $order['payment_method'] === 'eft' ? ' (EFT usually reflects within 1 to 2 working days)' : '' ?>.</li><?php endif; ?>
            <li>Your coffee is roasted on <?= e(setting('roast_day', 'Thursday')) ?> and ground to your choice the same day.</li>
            <li><?= $isCollection ? 'We email you when it is ready to collect.' : 'It ships by courier, ' . e(setting('delivery_estimate')) . '. We email you the tracking number.' ?></li>
            <li>A confirmation has been sent to <?= e($order['email']) ?>. Keep this page's link to check your order any time.</li>
          </ol>
        </div>
        <?php if ($order['notes'] !== ''): ?>
        <div class="order-box" data-reveal="right"><h3>Your notes</h3><p style="font-size:var(--text-sm);margin:0;white-space:pre-wrap"><?= e($order['notes']) ?></p></div>
        <?php endif; ?>
        <a class="btn btn--outline" href="<?= e(route('shop')) ?>">Continue shopping <?= icon('arrow', 16) ?></a>
      </div>
    </div>
  </div>
</section>

<?php render_footer(); ?>
