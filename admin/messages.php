<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

if (admin_post()) {
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'read' && $id) {
        db()->prepare('UPDATE messages SET is_read = 1 - is_read WHERE id = ?')->execute([$id]);
    } elseif ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
        flash('success', 'Message deleted.');
    } elseif ($action === 'read_all') {
        db()->exec('UPDATE messages SET is_read = 1');
    }
    redirect(admin_url('messages.php'));
}

$rows = db()->query('SELECT * FROM messages ORDER BY is_read ASC, created_at DESC LIMIT 300')->fetchAll();
admin_header('Messages', 'messages', 'From the contact form');
?>
<div class="admin-card">
  <div class="flex between" style="margin-bottom:14px">
    <p class="muted" style="margin:0;font-size:var(--text-sm)"><?= count($rows) ?> messages. Reply from your own email; the sender's address is on each card.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn--sm btn--outline" type="submit">Mark all read</button></form>
  </div>
  <?php if (!$rows): ?><p class="muted" style="margin:0">No messages yet.</p><?php endif; ?>
  <?php foreach ($rows as $m): ?>
    <div class="msg <?= (int) $m['is_read'] ? '' : 'is-unread' ?>">
      <div class="msg__head">
        <div><strong><?= e($m['name']) ?></strong> <span>· <a href="mailto:<?= e($m['email']) ?>?subject=Re: <?= e(rawurlencode($m['subject'] ?: 'your message')) ?>"><?= e($m['email']) ?></a> · <?= e(admin_date($m['created_at'])) ?></span><?php if ($m['subject'] !== ''): ?><br><span><?= e($m['subject']) ?></span><?php endif; ?></div>
        <div class="flex" style="gap:6px">
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit"><?= (int) $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button></form>
          <form method="post" style="display:inline" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit" style="color:var(--error)"><?= icon('trash', 14) ?></button></form>
        </div>
      </div>
      <p><?= e($m['message']) ?></p>
    </div>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
