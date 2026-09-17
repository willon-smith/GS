<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'subscribed_at']);
    foreach (db()->query('SELECT email, created_at FROM subscribers ORDER BY created_at DESC') as $r) {
        fputcsv($out, [$r['email'], $r['created_at']]);
    }
    fclose($out);
    exit;
}

if (admin_post()) {
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete' && $id) {
        db()->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$id]);
        flash('success', 'Subscriber removed.');
    } elseif (($_POST['action'] ?? '') === 'add') {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            db()->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (?)')->execute([$email]);
            flash('success', $email . ' added.');
        } else {
            flash('error', 'That is not a valid email address.');
        }
    }
    redirect(admin_url('subscribers.php'));
}

$rows = db()->query('SELECT * FROM subscribers ORDER BY created_at DESC')->fetchAll();
admin_header('Subscribers', 'subscribers', count($rows) . ' on the list');
?>
<div class="admin-form">
  <div class="admin-card">
    <div class="flex between" style="margin-bottom:12px">
      <p class="muted" style="margin:0;font-size:var(--text-sm)">People who asked to hear about new harvests.</p>
      <a class="btn btn--sm btn--outline" href="<?= e(admin_url('subscribers.php?export=1')) ?>">Export CSV</a>
    </div>
    <?php if (!$rows): ?><p class="muted" style="margin:0">Nobody has subscribed yet.</p><?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Email</th><th>Subscribed</th><th class="actions"></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['email']) ?></td><td><?= e(admin_date($r['created_at'])) ?></td>
          <td class="actions"><form method="post" data-confirm="Remove this subscriber?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit" style="color:var(--error)"><?= icon('trash', 14) ?></button></form></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>
  <div class="admin-card">
    <h2>Add by hand</h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <div class="field"><label class="field__label" for="s-email">Email</label><input class="input" id="s-email" type="email" name="email" required></div>
      <div><button class="btn" type="submit">Add subscriber</button></div>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
