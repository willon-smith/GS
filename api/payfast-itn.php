<?php
/**
 * PayFast Instant Transaction Notification handler.
 *
 * PayFast posts here after a payment. This needs a public URL to work, so it
 * does nothing on localhost; the order page still shows "awaiting confirmation"
 * until you mark the order paid in the admin or the site is live.
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

header('HTTP/1.0 200 OK');
flush();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !payfast_enabled()) {
    exit;
}

$data = $_POST;
$log = static function (string $line): void {
    @file_put_contents(dirname(DB_PATH) . '/payfast.log', date('Y-m-d H:i:s') . ' ' . $line . "\n", FILE_APPEND);
};

// 1. Signature: all fields except signature, in the order received.
$check = $data;
unset($check['signature']);
$sig = payfast_signature($check, setting('payfast_passphrase'));
if (!isset($data['signature']) || !hash_equals($sig, (string) $data['signature'])) {
    $log('Invalid signature for ' . ($data['m_payment_id'] ?? '?'));
    exit;
}

// 2. The order must exist and the amount must match.
$order = order_by_ref((string) ($data['m_payment_id'] ?? ''));
if (!$order) {
    $log('Unknown order ' . ($data['m_payment_id'] ?? '?'));
    exit;
}
$paid = (float) ($data['amount_gross'] ?? 0);
if (abs($paid - $order['total_cents'] / 100) > 0.01) {
    $log("Amount mismatch for {$order['ref']}: {$paid}");
    exit;
}

// 3. Ask PayFast to confirm the notification is theirs.
$host = setting('payfast_sandbox', '1') === '1' ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
$valid = true;
if (function_exists('curl_init')) {
    $ch = curl_init("https://{$host}/eng/query/validate");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($check),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $valid = is_string($res) && str_starts_with(trim($res), 'VALID');
}
if (!$valid) {
    $log("Server validation failed for {$order['ref']}");
    exit;
}

// 4. Update the order.
$status = (string) ($data['payment_status'] ?? '');
$pfId   = (string) ($data['pf_payment_id'] ?? '');
if ($status === 'COMPLETE' && $order['status'] === 'pending_payment') {
    order_set_status((int) $order['id'], 'paid', $pfId);
    $log("Order {$order['ref']} paid ({$pfId})");
} elseif ($status === 'CANCELLED') {
    $log("Order {$order['ref']} cancelled at PayFast");
}
