<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

$id = (int) ($_GET['id'] ?? 0);
$isNew = $id === 0;
$p = $isNew ? null : product_by_id($id, false);
if (!$isNew && !$p) {
    flash('error', 'That product does not exist.');
    redirect(admin_url('products.php'));
}

$defaults = [
    'slug' => '', 'group_key' => setting('featured_group'), 'name' => '', 'tagline' => '', 'description' => '',
    'origin' => '', 'region' => '', 'process' => '', 'altitude' => '', 'varietal' => '',
    'roast_level' => 3, 'acidity' => 3, 'body' => 3, 'sweetness' => 3, 'tasting_notes' => '',
    'weight_g' => 250, 'size_label' => '250g', 'price_cents' => 0, 'compare_price_cents' => 0, 'cups' => 15,
    'badge' => '', 'accent' => '#C98B55', 'image' => '', 'stock' => -1, 'active' => 1, 'sort' => 0,
];
$v = $p ? array_intersect_key($p, $defaults) + $defaults : $defaults;
$errors = [];

if (admin_post()) {
    if (($_POST['action'] ?? '') === 'remove_image' && $p) {
        delete_upload($p['image']);
        db()->prepare('UPDATE products SET image = \'\' WHERE id = ?')->execute([$id]);
        flash('success', 'Image removed; the generated bag is shown instead.');
        redirect(admin_url('product-edit.php?id=' . $id));
    }

    foreach (['slug', 'group_key', 'name', 'tagline', 'description', 'origin', 'region', 'process', 'altitude', 'varietal', 'tasting_notes', 'size_label', 'badge', 'accent'] as $k) {
        $v[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    foreach (['roast_level', 'acidity', 'body', 'sweetness', 'weight_g', 'cups', 'sort'] as $k) {
        $v[$k] = (int) ($_POST[$k] ?? 0);
    }
    $v['price_cents']         = (int) round(((float) str_replace(',', '.', (string) ($_POST['price'] ?? '0'))) * 100);
    $v['compare_price_cents'] = (int) round(((float) str_replace(',', '.', (string) ($_POST['compare_price'] ?? '0'))) * 100);
    $v['stock']  = ($_POST['stock'] ?? '') === '' ? -1 : max(0, (int) $_POST['stock']);
    $v['active'] = !empty($_POST['active']) ? 1 : 0;
    $v['group_key'] = slugify($v['group_key']);
    if ($v['slug'] === '') { $v['slug'] = slugify($v['name'] . ' ' . $v['size_label']); }
    $v['slug'] = slugify($v['slug']);

    if ($v['name'] === '') { $errors['name'] = 'Give the product a name.'; }
    if ($v['size_label'] === '') { $errors['size_label'] = 'Add a size label, for example 250g.'; }
    if ($v['price_cents'] <= 0) { $errors['price'] = 'Enter a price greater than zero.'; }
    if ($v['weight_g'] <= 0) { $errors['weight_g'] = 'Enter the weight in grams.'; }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $v['accent'])) { $v['accent'] = '#C98B55'; }
    foreach (['roast_level', 'acidity', 'body', 'sweetness'] as $k) { $v[$k] = max(1, min(5, $v[$k])); }
    $chk = db()->prepare('SELECT id FROM products WHERE slug = ? AND id <> ?');
    $chk->execute([$v['slug'], $id]);
    if ($chk->fetch()) { $errors['slug'] = 'Another product already uses that slug.'; }

    if (!$errors && !empty($_FILES['image']['name'])) {
        try {
            $new = upload_product_image($_FILES['image'], $v['slug']);
            if ($v['image'] !== '') { delete_upload($v['image']); }
            $v['image'] = $new;
        } catch (RuntimeException $ex) {
            $errors['image'] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $cols = array_keys($defaults);
        if ($isNew) {
            if ($v['sort'] === 0) { $v['sort'] = (int) db()->query('SELECT COALESCE(MAX(sort),0)+1 FROM products')->fetchColumn(); }
            $sql = 'INSERT INTO products (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
            db()->prepare($sql)->execute(array_map(fn($k) => $v[$k], $cols));
            $id = (int) db()->lastInsertId();
            flash('success', $v['name'] . ' ' . $v['size_label'] . ' created.');
        } else {
            $sets = implode(', ', array_map(fn($k) => "$k = ?", $cols));
            db()->prepare("UPDATE products SET $sets, updated_at = datetime('now') WHERE id = ?")->execute([...array_map(fn($k) => $v[$k], $cols), $id]);
            flash('success', $v['name'] . ' ' . $v['size_label'] . ' saved.');
        }
        redirect(admin_url('products.php'));
    }
    flash('error', 'Please check the highlighted fields.');
}

$err = fn(string $k) => isset($errors[$k]) ? ' is-error' : '';
$msg = fn(string $k) => isset($errors[$k]) ? '<span class="field__error">' . e($errors[$k]) . '</span>' : '';
admin_header($isNew ? 'Add product' : 'Edit product', 'products', $isNew ? 'A new bag on the shelf' : $v['name'] . ' · ' . $v['size_label']);
?>
<p><a class="link" href="<?= e(admin_url('products.php')) ?>"><?= icon('arrow', 14) ?> Back to products</a></p>
<form method="post" enctype="multipart/form-data" class="admin-form" id="product-form">
  <?= csrf_field() ?>
  <div>
    <div class="admin-card">
      <h2>Basics</h2>
      <div class="form-grid">
        <div class="form-row">
          <div class="field<?= $err('name') ?>"><label class="field__label" for="name">Name</label><input class="input" id="name" name="name" value="<?= e($v['name']) ?>" required><?= $msg('name') ?></div>
          <div class="field<?= $err('size_label') ?>"><label class="field__label" for="size_label">Size label</label><input class="input" id="size_label" name="size_label" value="<?= e($v['size_label']) ?>" placeholder="250g"><?= $msg('size_label') ?></div>
        </div>
        <div class="form-row">
          <div class="field<?= $err('slug') ?>"><label class="field__label" for="slug">Slug <small>in the URL</small></label><input class="input" id="slug" name="slug" value="<?= e($v['slug']) ?>" data-slug-from="#name" <?= $v['slug'] !== '' ? 'data-touched="1"' : '' ?>><?= $msg('slug') ?></div>
          <div class="field"><label class="field__label" for="group_key">Group key <small>same key = same coffee, different sizes</small></label><input class="input" id="group_key" name="group_key" value="<?= e($v['group_key']) ?>" placeholder="stewards-reserve"></div>
        </div>
        <div class="field"><label class="field__label" for="tagline">Tagline</label><input class="input" id="tagline" name="tagline" value="<?= e($v['tagline']) ?>" placeholder="For the daily ritual"></div>
        <div class="field"><label class="field__label" for="description">Description <small>blank line between paragraphs</small></label><textarea class="textarea" id="description" name="description" rows="6"><?= e($v['description']) ?></textarea></div>
      </div>
    </div>

    <div class="admin-card">
      <h2>Pricing and stock</h2>
      <div class="form-grid">
        <div class="form-row">
          <div class="field<?= $err('price') ?>"><label class="field__label" for="price">Price (<?= e(setting('currency_symbol', 'R')) ?>)</label><input class="input" id="price" name="price" inputmode="decimal" value="<?= $v['price_cents'] > 0 ? number_format($v['price_cents'] / 100, 2, '.', '') : '' ?>" placeholder="165.00"><?= $msg('price') ?></div>
          <div class="field"><label class="field__label" for="compare_price">Compare-at price <small>optional, shown struck through</small></label><input class="input" id="compare_price" name="compare_price" inputmode="decimal" value="<?= $v['compare_price_cents'] > 0 ? number_format($v['compare_price_cents'] / 100, 2, '.', '') : '' ?>"></div>
        </div>
        <div class="form-row">
          <div class="field<?= $err('weight_g') ?>"><label class="field__label" for="weight_g">Weight in grams</label><input class="input" id="weight_g" name="weight_g" type="number" min="1" value="<?= (int) $v['weight_g'] ?>"><?= $msg('weight_g') ?></div>
          <div class="field"><label class="field__label" for="cups">Approximate cups per bag</label><input class="input" id="cups" name="cups" type="number" min="0" value="<?= (int) $v['cups'] ?>"><span class="admin-help">Weight divided by about 16g per cup.</span></div>
        </div>
        <div class="form-row">
          <div class="field"><label class="field__label" for="stock">Stock <small>leave empty for unlimited</small></label><input class="input" id="stock" name="stock" type="number" min="0" value="<?= (int) $v['stock'] >= 0 ? (int) $v['stock'] : '' ?>"></div>
          <div class="field"><label class="field__label" for="badge">Badge <small>optional</small></label><input class="input" id="badge" name="badge" value="<?= e($v['badge']) ?>" placeholder="Most popular"></div>
        </div>
      </div>
    </div>

    <div class="admin-card">
      <h2>Coffee profile</h2>
      <div class="form-grid">
        <div class="form-row">
          <div class="field"><label class="field__label" for="origin">Origin</label><input class="input" id="origin" name="origin" value="<?= e($v['origin']) ?>" placeholder="Ethiopia"></div>
          <div class="field"><label class="field__label" for="region">Region</label><input class="input" id="region" name="region" value="<?= e($v['region']) ?>" placeholder="Yirgacheffe, Gedeo Zone"></div>
        </div>
        <div class="form-row">
          <div class="field"><label class="field__label" for="process">Process</label><input class="input" id="process" name="process" value="<?= e($v['process']) ?>"></div>
          <div class="field"><label class="field__label" for="altitude">Altitude</label><input class="input" id="altitude" name="altitude" value="<?= e($v['altitude']) ?>"></div>
        </div>
        <div class="field"><label class="field__label" for="varietal">Varietal</label><input class="input" id="varietal" name="varietal" value="<?= e($v['varietal']) ?>"></div>
        <div class="field"><label class="field__label" for="tasting_notes">Tasting notes <small>comma separated, first three appear on the bag</small></label><input class="input" id="tasting_notes" name="tasting_notes" value="<?= e($v['tasting_notes']) ?>" placeholder="Bergamot, Honey, Stone fruit"></div>
        <div class="form-row">
          <?php foreach (['roast_level' => 'Roast level', 'acidity' => 'Acidity'] as $k => $label): ?>
            <div class="field"><label class="field__label" for="<?= $k ?>"><?= $label ?> <small>1 to 5</small></label><select class="select" id="<?= $k ?>" name="<?= $k ?>"><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= (int) $v[$k] === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
          <?php endforeach; ?>
        </div>
        <div class="form-row">
          <?php foreach (['body' => 'Body', 'sweetness' => 'Sweetness'] as $k => $label): ?>
            <div class="field"><label class="field__label" for="<?= $k ?>"><?= $label ?> <small>1 to 5</small></label><select class="select" id="<?= $k ?>" name="<?= $k ?>"><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= (int) $v[$k] === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="admin-card">
      <h2>Appearance</h2>
      <div class="preview-box" id="bag-preview" style="--accent:<?= e($v['accent']) ?>" data-image-preview>
        <?php if ($v['image'] !== '' && file_exists(UPLOAD_DIR . '/' . $v['image'])): ?>
          <img src="<?= e(url('uploads/' . rawurlencode($v['image']))) ?>" alt="">
        <?php else: ?>
          <?= svg_bag($v, 'preview') ?>
        <?php endif; ?>
      </div>
      <p class="admin-help">Without a photo, the site draws this bag from the product details. Upload a photo to replace it.</p>
      <div class="field mt-3<?= $err('image') ?>"><label class="field__label" for="image">Photo <small>JPG, PNG or WebP, under 5 MB</small></label><input class="input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-image-input><?= $msg('image') ?></div>
      <?php if ($v['image'] !== ''): ?>
        <button class="btn btn--sm btn--ghost mt-2" type="submit" name="action" value="remove_image" formnovalidate style="color:var(--error)"><?= icon('trash', 14) ?> Remove photo</button>
      <?php endif; ?>
      <div class="field mt-3"><label class="field__label" for="accent">Accent colour <small>label band and glow</small></label>
        <div class="color-field"><input type="color" id="accent" value="<?= e($v['accent']) ?>" data-color-preview="#bag-preview"><input class="input" name="accent" value="<?= e($v['accent']) ?>" data-accent-text pattern="#[0-9a-fA-F]{6}"></div>
      </div>
    </div>
    <div class="admin-card">
      <h2>Publish</h2>
      <label class="switch"><input type="checkbox" name="active" value="1" <?= (int) $v['active'] ? 'checked' : '' ?>><span class="switch__track"></span> Live on the site</label>
      <div class="field mt-3"><label class="field__label" for="sort">Sort position <small>lower shows first</small></label><input class="input" id="sort" name="sort" type="number" value="<?= (int) $v['sort'] ?>"></div>
      <button class="btn btn--block btn--lg mt-3" type="submit"><?= $isNew ? 'Create product' : 'Save changes' ?></button>
    </div>
  </div>
</form>
<?php admin_footer(); ?>
