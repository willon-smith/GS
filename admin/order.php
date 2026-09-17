<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

$id = (int) ($_GET['id'] ?? 0);
$order = $id > 0 ? order_by_id($id) : null;
if (!$order) {
    flash('error', 'That order does not exist.');
    redirect(admin_url('orders.php'));
}

if (admin_post()) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'status') {
        $new = (string) ($_POST['status'] ?? '');
        $ref = trim((string) ($_POST['payment_ref'] ?? ''));
        if (isset(order_statuses()[$new])) {
            order_set_status($id, $new, $ref);
            if ($ref !== '') {
                db()->prepare('UPDATE orders SET payment_ref = ? WHERE id = ?')->execute([$ref, $id]);
            }
            flash('success', 'Order ' . $order['ref'] . ' is now "' . status_label($new) . '".');
        }
    } elseif ($action === 'resend') {
        send_order_email($order);
        flash('success', 'Confirmation email sent to ' . $order['email'] . (setting('mail_enabled') === '1' ? '.' : ' (mail is off, so it was written to data/mail.log).'));
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
        flash('success', 'Order ' . $order['ref'] . ' deleted.');
        redirect(admin_url('orders.php'));
    }
    redirect(admin_url('order.php?id=' . $id));
}

$isCollection = $order['delivery_method'] === 'collection';
admin_header('Order ' . $order['ref'], 'orders', 'Placed ' . admin_date($order['created_at']) . ' · ' . ($order['payment_method'] === 'payfast' ? 'PayFast' : 'Manual EFT'));
?>
<p><a class="link" href="<?= e(admin_url('orders.php')) ?>"><?= icon('arrow', 14) ?> Back to orders</a></p>
<div class="admin-form">
  <div>
    <div class="admin-card">
      <h2>Items</h2>
      <?php foreach ($order['items'] as $it): ?>
        <div class="item-line"><div><?= e($it['product_name']) ?> · <?= e($it['size_label']) ?><small><?= e($it['grind_name']) ?> · <?= money((int) $it['unit_cents']) ?> each</small></div><div>× <?= (int) $it['qty'] ?></div><strong><?= money((int) $it['line_cents']) ?></strong></div>
      <?php endforeach; ?>
      <div class="summary__line"><span>Subtotal</span><span><?= money((int) $order['subtotal_cents']) ?></span></div>
      <div class="summary__line"><span><?= $isCollection ? 'Collection' : 'Courier' ?></span><span><?= (int) $order['shipping_cents'] === 0 ? 'Free' : money((int) $order['shipping_cents']) ?></span></div>
      <div class="summary__total"><span>Total</span><strong><?= money((int) $order['total_cents']) ?></strong></div>
    </div>

    <div class="admin-card">
      <h2>Customer</h2>
      <div class="order-grid" style="gap:16px">
        <div>
          <p style="margin:0 0 6px"><strong><?= e($order['first_name'] . ' ' . $order['last_name']) ?></strong></p>
          <p style="margin:0;font-size:var(--text-sm)"><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a><br><a href="tel:<?= e(preg_replace('/\s+/', '', $order['phone'])) ?>"><?= e($order['phone']) ?></a></p>
        </div>
        <div>
          <p class="kpi__label" style="margin-bottom:6px"><?= $isCollection ? 'Collection' : 'Deliver to' ?></p>
          <?php if ($isCollection): ?>
            <p style="margin:0;font-size:var(--text-sm)">Customer collects from the roastery.</p>
          <?php else: ?>
            <address style="font-style:normal;font-size:var(--text-sm);line-height:1.7"><?= e($order['address1']) ?><br><?= $order['address2'] !== '' ? e($order['address2']) . '<br>' : '' ?><?= e($order['suburb']) ?>, <?= e($order['city']) ?><br><?= e($order['province']) ?>, <?= e($order['postcode']) ?></address>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($order['notes'] !== ''): ?><p class="kpi__label mt-3" style="margin-bottom:6px">Notes from the customer</p><p style="margin:0;font-size:var(--text-sm);white-space:pre-wrap"><?= e($order['notes']) ?></p><?php endif; ?>
    </div>
  </div>

  <div>
    <div class="admin-card">
      <h2>Status</h2>
      <p><span class="status status--<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span></p>
      <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="status">
        <div class="field"><label class="field__label" for="status">Move to</label>
          <select class="select" id="status" name="status">
            <?php foreach (order_statuses() as $k => $label): ?><option value="<?= e($k) ?>" <?= $k === $order['status'] ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label class="field__label" for="payment_ref">Payment reference <small>optional</small></label><input class="input" id="payment_ref" name="payment_ref" value="<?= e($order['payment_ref']) ?>" placeholder="Bank reference or PayFast ID"></div>
        <button class="btn btn--block" type="submit">Update order</button>
      </form>
      <p class="admin-help mt-2">Typical flow: awaiting payment, paid, roasting, shipped or ready for collection, completed.</p>
    </div>
    <div class="admin-card">
      <h2>Actions</h2>
      <form method="post" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="resend">
        <button class="btn btn--outline btn--block" type="submit"><?= icon('mail', 16) ?> Resend confirmation email</button>
      </form>
      <p class="admin-help mt-2">Customer link: <a href="<?= e(route('order', $order['ref'], ['key' => $order['access_key']])) ?>" target="_blank" rel="noopener">open order page</a></p>
      <form method="post" class="mt-3" data-confirm="Delete this order permanently?">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <button class="btn btn--ghost btn--sm" type="submit" style="color:var(--error)"><?= icon('trash', 14) ?> Delete order</button>
      </form>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
