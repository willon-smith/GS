<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

$status = (string) ($_GET['status'] ?? '');
$q      = trim((string) ($_GET['q'] ?? ''));
$where  = [];
$params = [];
if ($status !== '' && isset(order_statuses()[$status])) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(o.ref LIKE ? OR o.email LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql = 'SELECT o.*, (SELECT SUM(qty) FROM order_items i WHERE i.order_id = o.id) AS items FROM orders o'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.created_at DESC LIMIT 200';
$st = db()->prepare($sql);
$st->execute($params);
$orders = $st->fetchAll();

$counts = [];
foreach (db()->query('SELECT status, COUNT(*) AS c FROM orders GROUP BY status') as $r) {
    $counts[$r['status']] = (int) $r['c'];
}

admin_header('Orders', 'orders', count($orders) . ' shown');
?>
<div class="admin-card">
  <form class="admin-toolbar" method="get">
    <a class="filter-pill<?= $status === '' ? ' is-active' : '' ?>" href="<?= e(admin_url('orders.php')) ?>">All</a>
    <?php foreach (order_statuses() as $k => $label): ?>
      <a class="filter-pill<?= $status === $k ? ' is-active' : '' ?>" href="<?= e(admin_url('orders.php?status=' . $k)) ?>"><?= e($label) ?><?= isset($counts[$k]) ? ' · ' . $counts[$k] : '' ?></a>
    <?php endforeach; ?>
    <span style="flex:1"></span>
    <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search ref, name or email">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <button class="btn btn--sm" type="submit">Search</button>
  </form>
</div>

<div class="admin-card">
  <?php if (!$orders): ?>
    <p class="muted" style="margin:0">No orders match.</p>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Delivery</th><th>Payment</th><th class="num">Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><a class="row-link" href="<?= e(admin_url('order.php?id=' . (int) $o['id'])) ?>"><?= e($o['ref']) ?></a><br><small class="muted"><?= (int) $o['items'] ?> item<?= (int) $o['items'] === 1 ? '' : 's' ?></small></td>
        <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?><br><small class="muted"><?= e($o['email']) ?></small></td>
        <td><?= e(admin_date($o['created_at'])) ?></td>
        <td><?= $o['delivery_method'] === 'collection' ? 'Collection' : e($o['city'] . ', ' . $o['province']) ?></td>
        <td><?= $o['payment_method'] === 'payfast' ? 'PayFast' : 'EFT' ?></td>
        <td class="num"><?= money((int) $o['total_cents']) ?></td>
        <td><span class="status status--<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
