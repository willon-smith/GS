<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

$icons = ['bean' => 'Whole bean', 'press' => 'French press', 'pourover' => 'Pour-over', 'moka' => 'Moka pot', 'espresso' => 'Espresso', 'cup' => 'Cup'];
$editId = (int) ($_GET['id'] ?? 0);

if (admin_post()) {
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM grinds WHERE id = ?')->execute([$id]);
        flash('success', 'Grind option removed.');
    } elseif ($action === 'toggle' && $id) {
        db()->prepare('UPDATE grinds SET active = 1 - active WHERE id = ?')->execute([$id]);
    } elseif ($action === 'save') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $key  = slugify((string) ($_POST['key'] ?? $name));
        $desc = trim((string) ($_POST['description'] ?? ''));
        $for  = trim((string) ($_POST['brewers'] ?? ''));
        $icon = isset($icons[$_POST['icon'] ?? '']) ? (string) $_POST['icon'] : 'bean';
        $sort = (int) ($_POST['sort'] ?? 0);
        if ($name === '') {
            flash('error', 'Give the grind a name.');
        } else {
            $chk = db()->prepare('SELECT id FROM grinds WHERE key = ? AND id <> ?');
            $chk->execute([$key, $id]);
            if ($chk->fetch()) {
                flash('error', 'Another grind already uses the key "' . $key . '".');
            } elseif ($id) {
                db()->prepare('UPDATE grinds SET key=?, name=?, description=?, brewers=?, icon=?, sort=? WHERE id=?')->execute([$key, $name, $desc, $for, $icon, $sort, $id]);
                flash('success', $name . ' saved.');
            } else {
                db()->prepare('INSERT INTO grinds (key, name, description, brewers, icon, active, sort) VALUES (?,?,?,?,?,1,?)')->execute([$key, $name, $desc, $for, $icon, $sort ?: (int) db()->query('SELECT COALESCE(MAX(sort),0)+1 FROM grinds')->fetchColumn()]);
                flash('success', $name . ' added.');
            }
        }
    }
    redirect(admin_url('grinds.php'));
}

$grinds = db()->query('SELECT * FROM grinds ORDER BY sort ASC, id ASC')->fetchAll();
$edit = null;
foreach ($grinds as $g) { if ((int) $g['id'] === $editId) { $edit = $g; } }
$v = $edit ?: ['id' => 0, 'key' => '', 'name' => '', 'description' => '', 'brewers' => '', 'icon' => 'bean', 'sort' => 0];

admin_header('Grind options', 'grinds', 'What customers choose when they add a bag');
?>
<div class="admin-form">
  <div class="admin-card">
    <div class="table-wrap"><table class="table">
      <thead><tr><th></th><th>Grind</th><th>For</th><th>Status</th><th class="actions"></th></tr></thead>
      <tbody>
      <?php foreach ($grinds as $g): ?>
        <tr>
          <td><span class="grind-card__icon" style="width:40px;height:40px;margin:0;border-radius:12px"><?= icon($g['icon'], 20) ?></span></td>
          <td><a class="row-link" href="<?= e(admin_url('grinds.php?id=' . (int) $g['id'])) ?>"><?= e($g['name']) ?></a><br><small class="muted"><?= e($g['description']) ?></small></td>
          <td><?= e($g['brewers']) ?></td>
          <td><span class="status <?= (int) $g['active'] ? 'status--completed' : 'status--cancelled' ?>"><?= (int) $g['active'] ? 'Live' : 'Hidden' ?></span></td>
          <td class="actions">
            <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit"><?= (int) $g['active'] ? 'Hide' : 'Show' ?></button></form>
            <form method="post" style="display:inline" data-confirm="Remove this grind option?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><button class="btn btn--sm btn--ghost" type="submit" style="color:var(--error)"><?= icon('trash', 14) ?></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <div class="admin-card">
    <h2><?= $edit ? 'Edit ' . e($edit['name']) : 'Add a grind option' ?></h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
      <div class="field"><label class="field__label" for="g-name">Name</label><input class="input" id="g-name" name="name" value="<?= e($v['name']) ?>" required></div>
      <div class="field"><label class="field__label" for="g-key">Key <small>optional, used internally</small></label><input class="input" id="g-key" name="key" value="<?= e($v['key']) ?>"></div>
      <div class="field"><label class="field__label" for="g-brewers">Brewers <small>shown under the name</small></label><input class="input" id="g-brewers" name="brewers" value="<?= e($v['brewers']) ?>" placeholder="French press, cold brew"></div>
      <div class="field"><label class="field__label" for="g-desc">Description</label><textarea class="textarea" id="g-desc" name="description" rows="3"><?= e($v['description']) ?></textarea></div>
      <div class="form-row">
        <div class="field"><label class="field__label" for="g-icon">Icon</label><select class="select" id="g-icon" name="icon"><?php foreach ($icons as $k => $label): ?><option value="<?= $k ?>" <?= $v['icon'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label class="field__label" for="g-sort">Sort</label><input class="input" id="g-sort" name="sort" type="number" value="<?= (int) $v['sort'] ?>"></div>
      </div>
      <div class="flex"><button class="btn" type="submit"><?= $edit ? 'Save' : 'Add grind' ?></button><?php if ($edit): ?><a class="btn btn--ghost" href="<?= e(admin_url('grinds.php')) ?>">Cancel</a><?php endif; ?></div>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
