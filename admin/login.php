<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';

if (admin_logged_in()) {
    redirect(admin_url());
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    if ($attempts >= 5 && time() - (int) ($_SESSION['login_last'] ?? 0) < 300) {
        $error = 'Too many attempts. Please wait five minutes and try again.';
    } elseif (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $st = db()->prepare('SELECT * FROM admin_users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $u['id'];
            unset($_SESSION['login_attempts'], $_SESSION['login_last']);
            $after = $_SESSION['admin_after_login'] ?? admin_url();
            unset($_SESSION['admin_after_login']);
            redirect(is_string($after) && str_contains($after, '/admin/') ? $after : admin_url());
        }
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['login_last'] = time();
        usleep(600000);
        $error = 'That username and password do not match.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sign in · Admin · <?= e(setting('store_name')) ?></title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=DM+Sans:opsz,wght@9..40,400..700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
</head>
<body>
<div class="admin-login">
  <div class="admin-login__card">
    <a class="brand" href="<?= e(route('home')) ?>">
      <?= svg_mark(40, '#0B1729', '#F4ECE1') ?>
      <span class="wordmark"><span class="wordmark__top">Commodities</span><span class="wordmark__main">Good Steward</span></span>
    </a>
    <h1>Roastery admin</h1>
    <p>Sign in to manage products, orders and settings.</p>
    <?php if ($error !== ''): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <div class="field"><label class="field__label" for="username">Username</label><input class="input" id="username" name="username" autocomplete="username" required autofocus></div>
      <div class="field"><label class="field__label" for="password">Password</label><input class="input" id="password" type="password" name="password" autocomplete="current-password" required></div>
      <button class="btn btn--lg btn--block" type="submit">Sign in <?= icon('arrow', 18) ?></button>
    </form>
    <p class="admin-help mt-3 text-center"><a href="<?= e(route('home')) ?>">Back to the site</a></p>
  </div>
</div>
</body>
</html>
