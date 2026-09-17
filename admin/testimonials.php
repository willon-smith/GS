<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

$editId = (int) ($_GET['id'] ?? 0);

if (admin_post()) {
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$id]);
        flash('success', 'Testimonial removed.');
    } elseif ($action === 'toggle' && $id) {
        db()->prepare('UPDATE testimonials SET active = 1 - active WHERE id = ?')->execute([$id]);
    } elseif ($action === 'save') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $loc  = trim((string) ($_POST['location'] ?? ''));
        $quote = trim((string) ($_POST['quote'] ?? ''));
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $sort = (int) ($_POST['sort'] ?? 0);
        if ($name === '' || $quote === '') {
            flash('error', 'A testimonial needs a name and a quote.');
        } elseif ($id) {
            db()->prepare('UPDATE testimonials SET name=?, location=?, quote=?, rating=?, sort=? WHERE id=?')->execute([$name, $loc, $quote, $rating, $sort, $id]);
            flash('success', 'Testimonial saved.');
        } else {
            db()->prepare('INSERT INTO testimonials (name, location, quote, rating, active, sort) VALUES (?,?,?,?,1,?)')->execute([$name, $loc, $quote, $rating, $sort ?: (int) db()->query('SELECT COALESCE(MAX(sort),0)+1 FROM testimonials')->fetchColumn()]);
            flash('success', 'Testimonial added.');
        }
    }
    redirect(admin_url('testimonials.php'));
}

$rows = db()->query('SELECT * FROM testimonials ORDER BY sort ASC, id ASC')->fetchAll();
$edit = null;
foreach ($rows as $r) { if ((int) $r['id'] === $editId) { $edit = $r; } }
$v = $edit ?: ['id' => 0, 'name' => '', 'location' => '', 'quote' => '', 'rating' => 5, 'sort' => 0];

admin_header('Testimonials', 'testimonials', 'Quotes shown on the home page');
?>
<div class="admin-form">
  <div class="admin-card">
    <?php if (!$rows): ?><p class="muted" style="margin:0">No testimonials yet.</p><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <div class="msg <?= (int) $r['active'] ? '' : 'is-unread' ?>" style="<?= (int) $r['active'] ? '' : 'opacity:.6' ?>">
        <div class="msg__head"><strong><?= e($r['name']) ?> <span>· <?= e($r['location']) ?> · <?= (int) $r['rating'] ?>/5</span></strong>
          <div class="flex" style="gap:6px">
            <a class="btn btn--sm btn--outline" href="<?= e(admin_url('testimonials.php?id=' . (int) $r['id'])) ?>">Edit</a>
            <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit"><?= (int) $r['active'] ? 'Hide' : 'Show' ?></button></form>
            <form method="post" style="display:inline" data-confirm="Delete this testimonial?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit" style="color:var(--error)"><?= icon('trash', 14) ?></button></form>
          </div>
        </div>
        <p><?= e($r['quote']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="admin-card">
    <h2><?= $edit ? 'Edit testimonial' : 'Add a testimonial' ?></h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
      <div class="form-row">
        <div class="field"><label class="field__label" for="t-name">Name</label><input class="input" id="t-name" name="name" value="<?= e($v['name']) ?>" required placeholder="Anelle M."></div>
        <div class="field"><label class="field__label" for="t-loc">Location</label><input class="input" id="t-loc" name="location" value="<?= e($v['location']) ?>" placeholder="Pretoria"></div>
      </div>
      <div class="field"><label class="field__label" for="t-quote">Quote</label><textarea class="textarea" id="t-quote" name="quote" rows="4" required><?= e($v['quote']) ?></textarea></div>
      <div class="form-row">
        <div class="field"><label class="field__label" for="t-rating">Stars</label><select class="select" id="t-rating" name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= (int) $v['rating'] === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
        <div class="field"><label class="field__label" for="t-sort">Sort</label><input class="input" id="t-sort" name="sort" type="number" value="<?= (int) $v['sort'] ?>"></div>
      </div>
      <div class="flex"><button class="btn" type="submit"><?= $edit ? 'Save' : 'Add testimonial' ?></button><?php if ($edit): ?><a class="btn btn--ghost" href="<?= e(admin_url('testimonials.php')) ?>">Cancel</a><?php endif; ?></div>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
