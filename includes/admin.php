<?php
/**
 * Admin: authentication guard, layout and shared helpers.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/svg.php';

function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function admin_require(): void
{
    if (!admin_logged_in()) {
        $_SESSION['admin_after_login'] = $_SERVER['REQUEST_URI'] ?? admin_url();
        redirect(admin_url('login.php'));
    }
}

function admin_user(): ?array
{
    if (!admin_logged_in()) {
        return null;
    }
    $st = db()->prepare('SELECT id, username, created_at FROM admin_users WHERE id = ?');
    $st->execute([(int) $_SESSION['admin_id']]);
    $u = $st->fetch();
    return $u ?: null;
}

/** True when this is a POST with a valid CSRF token. Flashes and returns false otherwise. */
function admin_post(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash('error', 'Your session expired. Please try that again.');
        return false;
    }
    return true;
}

function admin_counts(): array
{
    $pdo = db();
    return [
        'pending'  => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_payment'")->fetchColumn(),
        'active'   => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('paid','roasting')")->fetchColumn(),
        'unread'   => (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn(),
    ];
}

function admin_flashes(): void
{
    foreach (take_flashes() as $f) {
        echo '<div class="flash flash--' . e($f['type']) . '">' . icon($f['type'] === 'success' ? 'check' : ($f['type'] === 'error' ? 'close' : 'sparkle'), 18) . '<span>' . e($f['message']) . '</span></div>';
    }
}

function admin_header(string $title, string $active = '', string $subtitle = ''): void
{
    $counts = admin_counts();
    $nav = [
        ['index',        'Dashboard',    'grid',     ''],
        ['orders',       'Orders',       'box',      $counts['pending'] + $counts['active']],
        ['products',     'Products',     'tag',      ''],
        ['grinds',       'Grinds',       'bean',     ''],
        ['testimonials', 'Testimonials', 'star',     ''],
        ['messages',     'Messages',     'chat',     $counts['unread']],
        ['subscribers',  'Subscribers',  'mail',     ''],
        ['settings',     'Settings',     'settings', ''],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Admin · <?= e(setting('store_name')) ?></title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=DM+Sans:opsz,wght@9..40,400..700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
<style>:root{--grain:url("<?= grain_uri() ?>");}</style>
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-side" id="admin-side">
    <a class="brand" href="<?= e(admin_url()) ?>">
      <?= svg_mark(36, '#F4ECE1', '#0B1729') ?>
      <span class="wordmark"><span class="wordmark__top">Commodities</span><span class="wordmark__main">Good Steward</span></span>
    </a>
    <nav class="admin-nav" aria-label="Admin">
      <?php foreach ($nav as [$file, $label, $ic, $count]): ?>
        <a href="<?= e(admin_url($file . '.php')) ?>" class="<?= $active === $file ? 'is-active' : '' ?>"><?= icon($ic, 18) ?> <?= e($label) ?><?= $count !== '' && (int) $count > 0 ? '<span class="count">' . (int) $count . '</span>' : '' ?></a>
      <?php endforeach; ?>
      <a href="<?= e(route('home')) ?>" target="_blank" rel="noopener"><?= icon('external', 18) ?> View site</a>
      <a href="<?= e(admin_url('logout.php')) ?>"><?= icon('logout', 18) ?> Log out</a>
    </nav>
    <div class="admin-side__foot">Signed in as <?= e(admin_user()['username'] ?? '') ?></div>
  </aside>
  <main class="admin-main">
    <div class="admin-top">
      <div>
        <div class="flex" style="gap:10px">
          <button class="icon-btn admin-nav-toggle" type="button" id="admin-nav-toggle" aria-label="Menu"><?= icon('menu', 22) ?></button>
          <h1><?= e($title) ?></h1>
        </div>
        <?php if ($subtitle !== ''): ?><p><?= e($subtitle) ?></p><?php endif; ?>
      </div>
      <div id="admin-top-actions"></div>
    </div>
    <?php admin_flashes(); ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
<script>
(function () {
  var side = document.getElementById('admin-side'), t = document.getElementById('admin-nav-toggle');
  if (t) t.addEventListener('click', function () { side.classList.toggle('is-open'); });
  document.addEventListener('click', function (e) {
    var f = e.target.closest('form[data-confirm]');
    if (f && e.target.closest('button[type="submit"]') && !confirm(f.dataset.confirm)) e.preventDefault();
  });
  document.querySelectorAll('[data-color-preview]').forEach(function (inp) {
    var target = document.querySelector(inp.dataset.colorPreview);
    var sync = function () { if (target) target.style.setProperty('--accent', inp.value); document.querySelectorAll('[data-accent-text]').forEach(function (el) { el.value = inp.value; }); };
    inp.addEventListener('input', sync);
  });
  document.querySelectorAll('[data-slug-from]').forEach(function (inp) {
    var src = document.querySelector(inp.dataset.slugFrom), size = document.querySelector('#size_label');
    var gen = function () { if (inp.dataset.touched === '1') return; var s = ((src ? src.value : '') + ' ' + (size ? size.value : '')).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); inp.value = s; };
    inp.addEventListener('input', function () { inp.dataset.touched = '1'; });
    src && src.addEventListener('input', gen); size && size.addEventListener('input', gen);
  });
  var file = document.querySelector('[data-image-input]');
  if (file) file.addEventListener('change', function () {
    var box = document.querySelector('[data-image-preview]'); if (!box || !file.files[0]) return;
    var r = new FileReader(); r.onload = function () { box.innerHTML = '<img src="' + r.result + '" alt="">'; }; r.readAsDataURL(file.files[0]);
  });
})();
</script>
</body>
</html>
<?php
}

// ---------------------------------------------------------------------------
// Image uploads for products
// ---------------------------------------------------------------------------
function upload_product_image(array $file, string $slug): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image did not upload. Try a smaller file.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Images must be under 5 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $ext   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) {
        throw new RuntimeException('Please upload a JPG, PNG or WebP image.');
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $name = slugify($slug) . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $name;

    // Resize to a sensible maximum when GD is available; otherwise keep the original.
    $resized = false;
    if (function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            $max = 1400;
            if ($w > $max || $h > $max) {
                $scale = min($max / $w, $max / $h);
                $nw = (int) round($w * $scale); $nh = (int) round($h * $scale);
                $dst = imagecreatetruecolor($nw, $nh);
                imagealphablending($dst, false); imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($src); $src = $dst;
            }
            $ok = match ($ext) {
                'jpg'  => imagejpeg($src, $dest, 86),
                'png'  => imagepng($src, $dest, 6),
                'webp' => function_exists('imagewebp') ? imagewebp($src, $dest, 86) : false,
            };
            imagedestroy($src);
            $resized = (bool) $ok;
        }
    }
    if (!$resized && !move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the image. Check that the uploads folder is writable.');
    }
    return $name;
}

function delete_upload(string $name): void
{
    $name = basename($name);
    if ($name !== '' && file_exists(UPLOAD_DIR . '/' . $name)) {
        @unlink(UPLOAD_DIR . '/' . $name);
    }
}

function admin_date(string $sqlite): string
{
    $ts = strtotime($sqlite . ' UTC');
    return $ts ? date('j M Y, H:i', $ts) : $sqlite;
}
