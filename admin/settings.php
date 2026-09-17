<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';
admin_require();

$tabs = [
    'store'    => 'Store',
    'homepage' => 'Homepage',
    'delivery' => 'Delivery',
    'payments' => 'Payments',
    'email'    => 'Email and site',
    'account'  => 'Account',
];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? (string) $_GET['tab'] : 'store';

/** Field definitions per tab: key => [label, type, help] */
$fields = [
    'store' => [
        'store_name'       => ['Store name', 'text', ''],
        'store_short'      => ['Short name', 'text', 'Used where space is tight'],
        'tagline'          => ['Tagline', 'text', ''],
        'meta_description' => ['Search description', 'textarea', 'Shown under the title in Google, about 155 characters'],
        'announcement'     => ['Announcement bar', 'text', 'Separate items with a middle dot ( · ); they also scroll in the marquee'],
        'email'            => ['Email', 'text', ''],
        'phone'            => ['Phone', 'text', ''],
        'whatsapp'         => ['WhatsApp number', 'text', 'International format without plus, for example 27510000000'],
        'address_line1'    => ['Address line 1', 'text', ''],
        'address_line2'    => ['Address line 2', 'text', ''],
        'hours'            => ['Opening hours', 'text', ''],
        'instagram'        => ['Instagram URL', 'text', 'Leave empty to hide the icon'],
        'facebook'         => ['Facebook URL', 'text', 'Leave empty to hide the icon'],
    ],
    'homepage' => [
        'hero_eyebrow'      => ['Hero eyebrow', 'text', 'Small line above the headline'],
        'hero_title'        => ['Hero headline', 'text', 'The last word is set in italic gold'],
        'hero_subtitle'     => ['Hero paragraph', 'textarea', ''],
        'featured_group'    => ['Featured product group', 'text', 'Group key of the coffee shown in the hero and roast section'],
        'stewardship_intro' => ['Stewardship paragraph', 'textarea', ''],
        'roast_day'         => ['Roast day', 'text', 'For example Thursday'],
        'dispatch_note'     => ['Dispatch note', 'text', 'Shown on product pages and checkout'],
        'about_story'       => ['Our story', 'textarea', 'Shown on the About page; blank line between paragraphs'],
    ],
    'delivery' => [
        'shipping_fee_cents'            => ['Courier fee', 'money', ''],
        'free_shipping_threshold_cents' => ['Free delivery from', 'money', 'Order subtotal at which courier delivery becomes free'],
        'delivery_estimate'             => ['Delivery estimate', 'text', 'For example 2 to 4 working days nationwide'],
        'collection_enabled'            => ['Allow collection', 'toggle', ''],
        'collection_note'               => ['Collection note', 'textarea', ''],
    ],
    'payments' => [
        'eft_enabled'          => ['Accept manual EFT', 'toggle', ''],
        'eft_bank_name'        => ['Bank', 'text', ''],
        'eft_account_name'     => ['Account name', 'text', ''],
        'eft_account_number'   => ['Account number', 'text', ''],
        'eft_branch_code'      => ['Branch code', 'text', ''],
        'eft_note'             => ['EFT instructions', 'textarea', ''],
        'payfast_enabled'      => ['Accept PayFast', 'toggle', 'Card, Instant EFT, SnapScan and more'],
        'payfast_sandbox'      => ['PayFast sandbox mode', 'toggle', 'On while testing. The default merchant ID and key are PayFast sandbox test credentials'],
        'payfast_merchant_id'  => ['PayFast merchant ID', 'text', ''],
        'payfast_merchant_key' => ['PayFast merchant key', 'text', ''],
        'payfast_passphrase'   => ['PayFast passphrase', 'text', 'Must match the passphrase set in your PayFast account, or be empty in both'],
    ],
    'email' => [
        'mail_enabled'    => ['Send email', 'toggle', 'Off: emails are written to data/mail.log instead, which is what you want on localhost'],
        'mail_from'       => ['From address', 'text', ''],
        'pretty_urls'     => ['Pretty URLs', 'toggle', 'Turns /product.php?slug=x into /product/x. Needs Apache mod_rewrite and the .htaccess file; leave off under PHP\'s built-in server'],
        'currency_symbol' => ['Currency symbol', 'text', ''],
    ],
];

if (admin_post()) {
    if ($tab === 'account') {
        $u = admin_user();
        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        $username = trim((string) ($_POST['username'] ?? ''));
        $st = db()->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
        $st->execute([(int) $u['id']]);
        $hash = (string) $st->fetchColumn();
        if (!password_verify($current, $hash)) {
            flash('error', 'Your current password is not right.');
        } elseif ($username === '') {
            flash('error', 'Username cannot be empty.');
        } elseif ($new !== '' && strlen($new) < 8) {
            flash('error', 'Use at least 8 characters for the new password.');
        } elseif ($new !== $confirm) {
            flash('error', 'The new passwords do not match.');
        } else {
            if ($new !== '') {
                db()->prepare('UPDATE admin_users SET username = ?, password_hash = ? WHERE id = ?')->execute([$username, password_hash($new, PASSWORD_DEFAULT), (int) $u['id']]);
            } else {
                db()->prepare('UPDATE admin_users SET username = ? WHERE id = ?')->execute([$username, (int) $u['id']]);
            }
            flash('success', 'Account updated.');
        }
    } else {
        $pairs = [];
        foreach ($fields[$tab] as $key => [$label, $type]) {
            $raw = (string) ($_POST[$key] ?? '');
            $pairs[$key] = match ($type) {
                'toggle' => !empty($_POST[$key]) ? '1' : '0',
                'money'  => (string) (int) round(((float) str_replace(',', '.', $raw)) * 100),
                default  => trim($raw),
            };
        }
        settings_save($pairs);
        flash('success', $tabs[$tab] . ' settings saved.');
    }
    redirect(admin_url('settings.php?tab=' . $tab));
}

admin_header('Settings', 'settings', 'Everything the site shows comes from here');
?>
<div class="tabs">
  <?php foreach ($tabs as $k => $label): ?><a href="<?= e(admin_url('settings.php?tab=' . $k)) ?>" class="<?= $tab === $k ? 'is-active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
</div>

<?php if ($tab === 'account'): $u = admin_user(); ?>
<div class="admin-card" style="max-width:560px">
  <h2>Your login</h2>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <div class="field"><label class="field__label" for="username">Username</label><input class="input" id="username" name="username" value="<?= e($u['username'] ?? '') ?>" autocomplete="username"></div>
    <div class="field"><label class="field__label" for="current">Current password</label><input class="input" id="current" type="password" name="current" autocomplete="current-password" required></div>
    <div class="form-row">
      <div class="field"><label class="field__label" for="new">New password <small>leave empty to keep</small></label><input class="input" id="new" type="password" name="new" autocomplete="new-password"></div>
      <div class="field"><label class="field__label" for="confirm">Confirm new password</label><input class="input" id="confirm" type="password" name="confirm" autocomplete="new-password"></div>
    </div>
    <div><button class="btn" type="submit">Update account</button></div>
  </form>
  <p class="admin-help mt-3">The site ships with the username "admin" and the password "stewardship". Change it before the site goes live.</p>
</div>
<?php else: ?>
<div class="admin-card" style="max-width:760px">
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <?php foreach ($fields[$tab] as $key => [$label, $type, $help]): $val = setting($key); ?>
      <?php if ($type === 'toggle'): ?>
        <div class="field">
          <label class="switch"><input type="checkbox" name="<?= e($key) ?>" value="1" <?= $val === '1' ? 'checked' : '' ?>><span class="switch__track"></span> <?= e($label) ?></label>
          <?php if ($help): ?><span class="admin-help"><?= e($help) ?></span><?php endif; ?>
        </div>
      <?php elseif ($type === 'textarea'): ?>
        <div class="field"><label class="field__label" for="<?= e($key) ?>"><?= e($label) ?></label><textarea class="textarea" id="<?= e($key) ?>" name="<?= e($key) ?>" rows="<?= $key === 'about_story' ? 10 : 3 ?>"><?= e($val) ?></textarea><?php if ($help): ?><span class="admin-help"><?= e($help) ?></span><?php endif; ?></div>
      <?php elseif ($type === 'money'): ?>
        <div class="field" style="max-width:260px"><label class="field__label" for="<?= e($key) ?>"><?= e($label) ?> (<?= e(setting('currency_symbol', 'R')) ?>)</label><input class="input" id="<?= e($key) ?>" name="<?= e($key) ?>" inputmode="decimal" value="<?= number_format(((int) $val) / 100, 2, '.', '') ?>"><?php if ($help): ?><span class="admin-help"><?= e($help) ?></span><?php endif; ?></div>
      <?php else: ?>
        <div class="field"><label class="field__label" for="<?= e($key) ?>"><?= e($label) ?></label><input class="input" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($val) ?>"><?php if ($help): ?><span class="admin-help"><?= e($help) ?></span><?php endif; ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
    <div><button class="btn" type="submit">Save <?= e(strtolower($tabs[$tab])) ?> settings</button></div>
  </form>
</div>
<?php endif; ?>
<?php admin_footer(); ?>
