<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$c = cart();
if (!$c['items']) {
    redirect(route('cart'));
}

$collection = setting('collection_enabled') === '1';
$payfast    = payfast_enabled();
$eft        = setting('eft_enabled', '1') === '1';
$defaultPay = $payfast ? 'payfast' : 'eft';

$old = [
    'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '',
    'address1' => '', 'address2' => '', 'suburb' => '', 'city' => '', 'province' => '', 'postcode' => '',
    'delivery_method' => 'courier', 'payment_method' => $defaultPay, 'notes' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $v) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please check the form and submit again.';
    }
    if ($old['first_name'] === '') { $errors['first_name'] = 'Please enter your first name.'; }
    if ($old['last_name'] === '')  { $errors['last_name'] = 'Please enter your last name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Please enter a valid email address.'; }
    if (strlen(preg_replace('/\D/', '', $old['phone']) ?? '') < 9) { $errors['phone'] = 'Please enter a phone number the courier can call.'; }

    if (!in_array($old['delivery_method'], ['courier', 'collection'], true) || ($old['delivery_method'] === 'collection' && !$collection)) {
        $old['delivery_method'] = 'courier';
    }
    if ($old['delivery_method'] === 'courier') {
        if ($old['address1'] === '') { $errors['address1'] = 'Please enter a street address.'; }
        if ($old['suburb'] === '')   { $errors['suburb'] = 'Please enter a suburb.'; }
        if ($old['city'] === '')     { $errors['city'] = 'Please enter a city or town.'; }
        if (!in_array($old['province'], provinces(), true)) { $errors['province'] = 'Please choose a province.'; }
        if (!preg_match('/^\d{4}$/', $old['postcode'])) { $errors['postcode'] = 'Please enter a 4 digit postal code.'; }
    }

    $allowedPay = array_filter(['payfast' => $payfast, 'eft' => $eft]);
    if (!isset($allowedPay[$old['payment_method']])) {
        $errors['payment_method'] = 'Please choose a payment method.';
    }
    if (mb_strlen($old['notes']) > 1000) {
        $errors['notes'] = 'Please keep notes under 1,000 characters.';
    }

    if (!$errors) {
        try {
            $order = order_create($old);
            if ($order['payment_method'] === 'payfast') {
                $_SESSION['last_order'] = $order['ref'];
                render_header(['title' => 'Redirecting to PayFast', 'body_class' => 'page-checkout']);
                $fields = payfast_fields($order);
                ?>
                <section class="section section--foam">
                  <div class="container--narrow text-center">
                    <div class="success__check"><svg viewBox="0 0 24 24"><path d="M5 12l5 5L20 7"/></svg></div>
                    <h1 class="section__title">Taking you to <em>PayFast</em></h1>
                    <p class="section__lede" style="margin-inline:auto">Order <?= e($order['ref']) ?> is saved. If nothing happens in a few seconds, press the button below.</p>
                    <form id="payfast-form" action="<?= e(payfast_process_url()) ?>" method="post" class="mt-4">
                      <?php foreach ($fields as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>"><?php endforeach; ?>
                      <button class="btn btn--lg" type="submit">Pay <?= money((int) $order['total_cents']) ?> with PayFast <?= icon('arrow', 18) ?></button>
                    </form>
                    <p class="mt-3"><a class="link" href="<?= e(route('order', $order['ref'], ['key' => $order['access_key']])) ?>">View your order instead</a></p>
                  </div>
                </section>
                <script>setTimeout(function(){ document.getElementById('payfast-form').submit(); }, 1200);</script>
                <?php
                render_footer();
                exit;
            }
            redirect(route('order', $order['ref'], ['key' => $order['access_key']]));
        } catch (Throwable $ex) {
            $errors['form'] = APP_DEBUG ? $ex->getMessage() : 'We could not place your order. Please try again or contact us.';
        }
    }
}

render_header(['title' => 'Checkout', 'body_class' => 'page-checkout']);
$err = fn(string $k) => isset($errors[$k]) ? ' is-error' : '';
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(route('home')) ?>">Home</a> <?= icon('chevron') ?> <a href="<?= e(route('cart')) ?>">Your bag</a> <?= icon('chevron') ?> <span>Checkout</span></nav>
    <h1 class="page-hero__title">Nearly <em>there</em></h1>
    <p class="page-hero__lede">Tell us where to send it and how you would like to pay. <?= e(setting('dispatch_note')) ?></p>
  </div>
</section>

<section class="section section--foam">
  <div class="container">
    <?php if ($errors): ?>
      <div class="alert alert--error" role="alert">
        <strong>Please check the highlighted fields.</strong>
        <?php if (isset($errors['form'])): ?><div class="mt-1"><?= e($errors['form']) ?></div><?php endif; ?>
      </div>
    <?php endif; ?>

    <form class="checkout" method="post" action="<?= e(route('checkout')) ?>" id="checkout-form" novalidate>
      <?= csrf_field() ?>
      <div class="checkout__form">
        <div class="card" data-reveal>
          <div class="card__head"><span class="card__num">1</span><h3>Contact</h3></div>
          <div class="form-grid">
            <div class="form-row">
              <div class="field<?= $err('first_name') ?>"><label class="field__label" for="first_name">First name</label><input class="input" id="first_name" name="first_name" value="<?= e($old['first_name']) ?>" autocomplete="given-name" required><?php if (isset($errors['first_name'])): ?><span class="field__error"><?= e($errors['first_name']) ?></span><?php endif; ?></div>
              <div class="field<?= $err('last_name') ?>"><label class="field__label" for="last_name">Last name</label><input class="input" id="last_name" name="last_name" value="<?= e($old['last_name']) ?>" autocomplete="family-name" required><?php if (isset($errors['last_name'])): ?><span class="field__error"><?= e($errors['last_name']) ?></span><?php endif; ?></div>
            </div>
            <div class="form-row">
              <div class="field<?= $err('email') ?>"><label class="field__label" for="email">Email <small>for your order confirmation</small></label><input class="input" id="email" type="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required><?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?></div>
              <div class="field<?= $err('phone') ?>"><label class="field__label" for="phone">Phone <small>for the courier</small></label><input class="input" id="phone" type="tel" name="phone" value="<?= e($old['phone']) ?>" autocomplete="tel" required><?php if (isset($errors['phone'])): ?><span class="field__error"><?= e($errors['phone']) ?></span><?php endif; ?></div>
            </div>
          </div>
        </div>

        <div class="card" data-reveal>
          <div class="card__head"><span class="card__num">2</span><h3>Delivery</h3></div>
          <div class="tiles" id="delivery-tiles">
            <label class="tile">
              <input type="radio" name="delivery_method" value="courier" <?= $old['delivery_method'] === 'courier' ? 'checked' : '' ?>>
              <span class="tile__box">
                <?= icon('truck', 22) ?>
                <span><span class="tile__title">Courier to your door</span><span class="tile__desc"><?= e(setting('delivery_estimate')) ?>. Free over <?= money($c['threshold_cents']) ?>.</span></span>
                <span class="tile__price" data-ship-courier><?= $c['shipping_cents'] === 0 ? 'Free' : money($c['shipping_cents']) ?></span>
              </span>
            </label>
            <?php if ($collection): ?>
            <label class="tile">
              <input type="radio" name="delivery_method" value="collection" <?= $old['delivery_method'] === 'collection' ? 'checked' : '' ?>>
              <span class="tile__box">
                <?= icon('pin', 22) ?>
                <span><span class="tile__title">Collect from the roastery</span><span class="tile__desc"><?= e(setting('collection_note')) ?></span></span>
                <span class="tile__price">Free</span>
              </span>
            </label>
            <?php endif; ?>
          </div>

          <div class="form-grid mt-4" id="address-fields">
            <div class="field<?= $err('address1') ?>"><label class="field__label" for="address1">Street address</label><input class="input" id="address1" name="address1" value="<?= e($old['address1']) ?>" autocomplete="address-line1"><?php if (isset($errors['address1'])): ?><span class="field__error"><?= e($errors['address1']) ?></span><?php endif; ?></div>
            <div class="field"><label class="field__label" for="address2">Complex, unit or building <small>optional</small></label><input class="input" id="address2" name="address2" value="<?= e($old['address2']) ?>" autocomplete="address-line2"></div>
            <div class="form-row">
              <div class="field<?= $err('suburb') ?>"><label class="field__label" for="suburb">Suburb</label><input class="input" id="suburb" name="suburb" value="<?= e($old['suburb']) ?>"><?php if (isset($errors['suburb'])): ?><span class="field__error"><?= e($errors['suburb']) ?></span><?php endif; ?></div>
              <div class="field<?= $err('city') ?>"><label class="field__label" for="city">City or town</label><input class="input" id="city" name="city" value="<?= e($old['city']) ?>" autocomplete="address-level2"><?php if (isset($errors['city'])): ?><span class="field__error"><?= e($errors['city']) ?></span><?php endif; ?></div>
            </div>
            <div class="form-row">
              <div class="field<?= $err('province') ?>"><label class="field__label" for="province">Province</label>
                <select class="select" id="province" name="province">
                  <option value="">Choose a province</option>
                  <?php foreach (provinces() as $pr): ?><option value="<?= e($pr) ?>" <?= $old['province'] === $pr ? 'selected' : '' ?>><?= e($pr) ?></option><?php endforeach; ?>
                </select>
                <?php if (isset($errors['province'])): ?><span class="field__error"><?= e($errors['province']) ?></span><?php endif; ?>
              </div>
              <div class="field<?= $err('postcode') ?>"><label class="field__label" for="postcode">Postal code</label><input class="input" id="postcode" name="postcode" value="<?= e($old['postcode']) ?>" inputmode="numeric" maxlength="4" autocomplete="postal-code"><?php if (isset($errors['postcode'])): ?><span class="field__error"><?= e($errors['postcode']) ?></span><?php endif; ?></div>
            </div>
          </div>
        </div>

        <div class="card" data-reveal>
          <div class="card__head"><span class="card__num">3</span><h3>Payment</h3></div>
          <?php if (isset($errors['payment_method'])): ?><div class="alert alert--error"><?= e($errors['payment_method']) ?></div><?php endif; ?>
          <div class="tiles" id="payment-tiles">
            <?php if ($payfast): ?>
            <label class="tile">
              <input type="radio" name="payment_method" value="payfast" <?= $old['payment_method'] === 'payfast' ? 'checked' : '' ?>>
              <span class="tile__box">
                <?= icon('shield', 22) ?>
                <span><span class="tile__title">Card or instant EFT via PayFast</span><span class="tile__desc">Visa, Mastercard, Instant EFT, SnapScan and more. You are taken to PayFast to pay and brought straight back.</span></span>
                <span class="tile__price">Instant</span>
              </span>
            </label>
            <?php endif; ?>
            <?php if ($eft): ?>
            <label class="tile">
              <input type="radio" name="payment_method" value="eft" <?= $old['payment_method'] === 'eft' ? 'checked' : '' ?>>
              <span class="tile__box">
                <?= icon('scale', 22) ?>
                <span><span class="tile__title">Manual EFT</span><span class="tile__desc">Bank details are shown on the next page and emailed to you. We roast once payment reflects.</span></span>
                <span class="tile__price">1 to 2 days</span>
              </span>
            </label>
            <?php endif; ?>
            <?php if (!$payfast && !$eft): ?>
              <div class="alert alert--info">No payment method is enabled yet. Turn one on in the admin under Settings, Payments.</div>
            <?php endif; ?>
          </div>
          <div class="pay-detail" data-pay-detail="eft">
            Pay by EFT to:
            <dl>
              <dt>Bank</dt><dd><?= e(setting('eft_bank_name')) ?></dd>
              <dt>Account</dt><dd><?= e(setting('eft_account_name')) ?></dd>
              <dt>Number</dt><dd><?= e(setting('eft_account_number')) ?></dd>
              <dt>Branch</dt><dd><?= e(setting('eft_branch_code')) ?></dd>
            </dl>
          </div>
          <div class="field mt-4<?= $err('notes') ?>">
            <label class="field__label" for="notes">Order notes <small>optional: gate codes, delivery instructions, a gift message</small></label>
            <textarea class="textarea" id="notes" name="notes" rows="3"><?= e($old['notes']) ?></textarea>
          </div>
        </div>
      </div>

      <aside class="summary" data-reveal="right">
        <h3>Your order</h3>
        <div class="summary__items">
          <?php foreach ($c['items'] as $it): $p = $it['product']; ?>
            <div class="summary__item">
              <?php if ($img = product_image_url($p)): ?><img src="<?= e($img) ?>" alt=""><?php else: ?><?= svg_bag($p, 'sum-' . md5($it['key'])) ?><?php endif; ?>
              <div><?= e($p['name']) ?> · <?= e($p['size_label']) ?><small><?= e($it['grind']['name']) ?> × <?= (int) $it['qty'] ?></small></div>
              <strong><?= money($it['line_cents']) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="summary__line"><span>Subtotal</span><span><?= money($c['subtotal_cents']) ?></span></div>
        <div class="summary__line"><span>Delivery</span><span id="summary-shipping" data-courier="<?= $c['shipping_cents'] === 0 ? 'Free' : money($c['shipping_cents']) ?>" data-collection="Free"><?= $old['delivery_method'] === 'collection' ? 'Free' : ($c['shipping_cents'] === 0 ? 'Free' : money($c['shipping_cents'])) ?></span></div>
        <div class="summary__total"><span>Total</span><strong id="summary-total" data-courier="<?= money($c['total_cents']) ?>" data-collection="<?= money($c['subtotal_cents']) ?>"><?= $old['delivery_method'] === 'collection' ? money($c['subtotal_cents']) : money($c['total_cents']) ?></strong></div>
        <button class="btn btn--lg btn--block" type="submit" id="place-order" <?= (!$payfast && !$eft) ? 'disabled' : '' ?>>Place order <?= icon('arrow', 18) ?></button>
        <p class="secure"><?= icon('shield', 14) ?> Your details are only used to fulfil this order.</p>
        <p class="summary__note"><a href="<?= e(route('cart')) ?>">Edit your bag</a></p>
      </aside>
    </form>
  </div>
</section>

<?php render_footer(); ?>
