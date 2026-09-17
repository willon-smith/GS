<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$data  = request_json();
$email = trim((string) ($data['email'] ?? ''));
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'json');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (!$isAjax) { flash('error', 'Please enter a valid email address.'); redirect(route('home') . '#brew'); }
    json_response(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
}

// Light rate limit: one signup per session every 10 seconds.
$last = (int) ($_SESSION['newsletter_at'] ?? 0);
if (time() - $last < 10) {
    json_response(['ok' => true, 'message' => 'You are on the list.']);
}
$_SESSION['newsletter_at'] = time();

$st = db()->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (?)');
$st->execute([mb_strtolower($email)]);

if (!$isAjax) {
    flash('success', 'You are on the list. We will only write when a new harvest lands.');
    redirect(route('home'));
}
json_response(['ok' => true, 'message' => 'You are on the list. We will only write when a new harvest lands.']);
