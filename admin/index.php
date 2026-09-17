<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

$pdo = db();
$orders    = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$revenue   = (int) $pdo->query("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status NOT IN ('pending_payment','cancelled')")->fetchColumn();
$month     = (int) $pdo->query("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status NOT IN ('pending_payment','cancelled') AND created_at >= date('now','start of month')")->fetchColumn();
$pending   = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_payment'")->fetchColumn();
$toRoast   = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('paid','roasting')")->fetchColumn();
$subs      = (int) $pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
$unread    = (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
$recent    = $pdo->query('SELECT o.*, (SELECT SUM(qty) FROM order_items i WHERE i.order_id = o.id) AS items FROM orders o ORDER BY created_at DESC LIMIT 8')->fetchAll();
$lowStock  = $pdo->query('SELECT * FROM products WHERE stock >= 0 AND stock <= 5 AND active = 1 ORDER BY stock ASC')->fetchAll();
$topItems  = $pdo->query("SELECT product_name, size_label, grind_name, SUM(qty) AS q FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.status <> 'cancelled' GROUP BY product_name, size_label, grind_name ORDER BY q DESC LIMIT 5")->fetchAll();

admin_header('Dashboard', 'index', date('l, j F Y'));
?>
<div class="kpis">
  <div class="kpi"><div class="kpi__label">Revenue this month</div><div class="kpi__val"><?= money($month) ?></div><div class="kpi__sub"><?= money($revenue) ?> all time, paid orders only</div></div>
  <div class="kpi"><div class="kpi__label">Awaiting payment</div><div class="kpi__val"><?= $pending ?></div><div class="kpi__sub">EFT orders clear when you mark them paid</div></div>
  <div class="kpi"><div class="kpi__label">To roast and ship</div><div class="kpi__val"><?= $toRoast ?></div><div class="kpi__sub">Paid or roasting, not yet shipped</div></div>
  <div class="kpi"><div class="kpi__label">Subscribers</div><div class="kpi__val"><?= $subs ?></div><div class="kpi__sub"><?= $unread ?> unread <?= $unread === 1 ? 'message' : 'messages' ?></div></div>
</div>

<div class="admin-card">
  <div class="flex between" style="margin-bottom:12px"><h2 style="margin:0">Recent orders</h2><a class="link" href="<?= e(admin_url('orders.php')) ?>">All orders <?= icon('arrow', 14) ?></a></div>
  <?php if (!$recent): ?>
    <p class="muted" style="margin:0">No orders yet. When someone checks out on the site, it shows up here.</p>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Items</th><th class="num">Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $o): ?>
      <tr>
        <td><a class="row-link" href="<?= e(admin_url('order.php?id=' . (int) $o['id'])) ?>"><?= e($o['ref']) ?></a></td>
        <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?><br><small class="muted"><?= e($o['email']) ?></small></td>
        <td><?= e(admin_date($o['created_at'])) ?></td>
        <td><?= (int) $o['items'] ?></td>
        <td class="num"><?= money((int) $o['total_cents']) ?></td>
        <td><span class="status status--<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>

<div class="admin-form">
  <div class="admin-card">
    <h2>Best sellers</h2>
    <?php if (!$topItems): ?><p class="muted" style="margin:0">Nothing sold yet.</p><?php else: ?>
      <?php foreach ($topItems as $t): ?>
        <div class="item-line"><div><?= e($t['product_name']) ?> · <?= e($t['size_label']) ?><small><?= e($t['grind_name']) ?></small></div><div></div><strong><?= (int) $t['q'] ?> sold</strong></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="admin-card">
    <h2>Stock watch</h2>
    <?php if (!$lowStock): ?><p class="muted" style="margin:0">No products are low on stock. Products with unlimited stock are not tracked.</p><?php else: ?>
      <?php foreach ($lowStock as $p): ?>
        <div class="item-line"><div><?= e($p['name']) ?> · <?= e($p['size_label']) ?></div><div></div><strong style="color:<?= (int) $p['stock'] === 0 ? 'var(--error)' : 'var(--caramel)' ?>"><?= (int) $p['stock'] === 0 ? 'Sold out' : (int) $p['stock'] . ' left' ?></strong></div>
      <?php endforeach; ?>
    <?php endif; ?>
    <p class="admin-help mt-2"><a href="<?= e(admin_url('products.php')) ?>">Manage products</a></p>
  </div>
</div>
<?php admin_footer(); ?>
