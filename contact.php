<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$errors = [];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $v) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please send the message again.';
    }
    if ($old['name'] === '') { $errors['name'] = 'Please tell us your name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Please enter a valid email address.'; }
    if (mb_strlen($old['message']) < 10) { $errors['message'] = 'Please write a little more so we can help.'; }
    if (mb_strlen($old['message']) > 3000) { $errors['message'] = 'Please keep it under 3,000 characters.'; }
    if (!empty($_POST['website'])) { $errors['form'] = 'Something went wrong.'; } // honeypot
    $last = (int) ($_SESSION['contact_at'] ?? 0);
    if (time() - $last < 20) { $errors['form'] = 'Please wait a moment before sending another message.'; }

    if (!$errors) {
        $st = db()->prepare('INSERT INTO messages (name, email, subject, message) VALUES (?,?,?,?)');
        $st->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);
        $_SESSION['contact_at'] = time();
        send_mail(setting('email'), 'Website message from ' . $old['name'], "From: {$old['name']} <{$old['email']}>\nSubject: {$old['subject']}\n\n{$old['message']}");
        $sent = true;
        $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}

render_header(['title' => 'Contact', 'body_class' => 'page-contact', 'description' => 'Questions about an order, wholesale, or which grind to choose? Get in touch with the roastery.']);
$err = fn(string $k) => isset($errors[$k]) ? ' is-error' : '';
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(route('home')) ?>">Home</a> <?= icon('chevron') ?> <span>Contact</span></nav>
    <span class="eyebrow">Contact</span>
    <h1 class="page-hero__title">Talk to <em>the roastery</em></h1>
    <p class="page-hero__lede">Questions about an order, which grind to choose, wholesale for your café or office, or anything else. A person reads every message.</p>
  </div>
</section>

<section class="section section--foam">
  <div class="container contact__grid">
    <div data-reveal="left">
      <div class="contact-card">
        <div class="contact-card__icon"><?= icon('mail', 22) ?></div>
        <div><strong>Email</strong><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a><span>We reply within one working day.</span></div>
      </div>
      <div class="contact-card">
        <div class="contact-card__icon"><?= icon('phone', 22) ?></div>
        <div><strong>Phone and WhatsApp</strong><a href="tel:<?= e(preg_replace('/\s+/', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a><span><?= e(setting('hours')) ?></span></div>
      </div>
      <div class="contact-card">
        <div class="contact-card__icon"><?= icon('pin', 22) ?></div>
        <div><strong>The roastery</strong><span><?= e(setting('address_line1')) ?></span><span><?= e(setting('address_line2')) ?></span><span>Collections by arrangement.</span></div>
      </div>
      <div class="contact-card">
        <div class="contact-card__icon"><?= icon('box', 22) ?></div>
        <div><strong>Wholesale</strong><span>Cafés, offices and guesthouses: ask about weekly deliveries and equipment.</span></div>
      </div>
    </div>

    <div class="card" data-reveal>
      <?php if ($sent): ?>
        <div class="alert alert--success"><strong>Thank you.</strong> Your message is in, and we will reply to the address you gave us.</div>
      <?php endif; ?>
      <?php if (isset($errors['form'])): ?><div class="alert alert--error"><?= e($errors['form']) ?></div><?php endif; ?>
      <form method="post" action="<?= e(route('contact')) ?>" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-row">
          <div class="field<?= $err('name') ?>"><label class="field__label" for="c-name">Name</label><input class="input" id="c-name" name="name" value="<?= e($old['name']) ?>" required><?php if (isset($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?></div>
          <div class="field<?= $err('email') ?>"><label class="field__label" for="c-email">Email</label><input class="input" id="c-email" type="email" name="email" value="<?= e($old['email']) ?>" required><?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?></div>
        </div>
        <div class="field"><label class="field__label" for="c-subject">Subject <small>optional</small></label><input class="input" id="c-subject" name="subject" value="<?= e($old['subject']) ?>" placeholder="An order, wholesale, a grind question"></div>
        <div class="field<?= $err('message') ?>"><label class="field__label" for="c-message">Message</label><textarea class="textarea" id="c-message" name="message" required><?= e($old['message']) ?></textarea><?php if (isset($errors['message'])): ?><span class="field__error"><?= e($errors['message']) ?></span><?php endif; ?></div>
        <div><button class="btn btn--lg" type="submit">Send message <?= icon('arrow', 18) ?></button></div>
      </form>
    </div>
  </div>
</section>

<?php render_footer(); ?>
