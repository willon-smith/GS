<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

if (admin_post()) {
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $p = $id > 0 ? product_by_id($id, false) : null;
    if ($p) {
        if ($action === 'toggle') {
            db()->prepare('UPDATE products SET active = 1 - active, updated_at = datetime(\'now\') WHERE id = ?')->execute([$id]);
            flash('success', $p['name'] . ' ' . $p['size_label'] . ' is now ' . ((int) $p['active'] ? 'hidden from' : 'live on') . ' the site.');
        } elseif ($action === 'delete') {
            if ($p['image'] !== '') { delete_upload($p['image']); }
            db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            flash('success', $p['name'] . ' ' . $p['size_label'] . ' deleted.');
        } elseif ($action === 'move') {
            $dir = (string) ($_POST['dir'] ?? 'up');
            $all = products_all();
            $idx = array_search($id, array_column($all, 'id'), true);
            $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
            if ($idx !== false && isset($all[$swap])) {
                $st = db()->prepare('UPDATE products SET sort = ? WHERE id = ?');
                foreach ($all as $i => $row) {
                    $pos = $i === $idx ? $swap : ($i === $swap ? $idx : $i);
                    $st->execute([$pos + 1, (int) $row['id']]);
                }
            }
        }
    }
    redirect(admin_url('products.php'));
}

$products = products_all();
admin_header('Products', 'products', count($products) . ' products, ' . count(array_filter($products, fn($p) => (int) $p['active'])) . ' live');
?>
<div class="admin-card">
  <div class="flex between" style="margin-bottom:12px">
    <p class="muted" style="margin:0;font-size:var(--text-sm)">Products with the same group key show as size options of each other on the product page. Drag order with the arrows.</p>
    <a class="btn btn--sm" href="<?= e(admin_url('product-edit.php')) ?>"><?= icon('plus', 14) ?> Add product</a>
  </div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th></th><th>Product</th><th>Group</th><th class="num">Price</th><th>Stock</th><th>Status</th><th class="actions">Order</th><th class="actions"></th></tr></thead>
    <tbody>
    <?php foreach ($products as $i => $p): ?>
      <tr>
        <td><div class="thumb"><?php if ($img = product_image_url($p)): ?><img src="<?= e($img) ?>" alt=""><?php else: ?><?= svg_bag($p, 'adm-' . (int) $p['id']) ?><?php endif; ?></div></td>
        <td><a class="row-link" href="<?= e(admin_url('product-edit.php?id=' . (int) $p['id'])) ?>"><?= e($p['name']) ?> · <?= e($p['size_label']) ?></a><br><small class="muted"><?= e($p['slug']) ?><?= $p['badge'] !== '' ? ' · ' . e($p['badge']) : '' ?></small></td>
        <td><small><?= e($p['group_key']) ?></small></td>
        <td class="num"><?= money((int) $p['price_cents']) ?></td>
        <td><?= (int) $p['stock'] < 0 ? '<span class="muted">Unlimited</span>' : ((int) $p['stock'] === 0 ? '<span style="color:var(--error)">Sold out</span>' : (int) $p['stock']) ?></td>
        <td><span class="status <?= (int) $p['active'] ? 'status--completed' : 'status--cancelled' ?>"><?= (int) $p['active'] ? 'Live' : 'Hidden' ?></span></td>
        <td class="actions">
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="dir" value="up"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="icon-btn" type="submit" aria-label="Move up" <?= $i === 0 ? 'disabled' : '' ?>><?= icon('arrow-up', 16) ?></button></form>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="dir" value="down"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="icon-btn" type="submit" aria-label="Move down" <?= $i === count($products) - 1 ? 'disabled' : '' ?> style="transform:rotate(180deg)"><?= icon('arrow-up', 16) ?></button></form>
        </td>
        <td class="actions">
          <a class="btn btn--sm btn--outline" href="<?= e(admin_url('product-edit.php?id=' . (int) $p['id'])) ?>">Edit</a>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit"><?= (int) $p['active'] ? 'Hide' : 'Publish' ?></button></form>
          <form method="post" style="display:inline" data-confirm="Delete this product? Past orders keep their line items."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit" style="color:var(--error)"><?= icon('trash', 14) ?></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php admin_footer(); ?>
