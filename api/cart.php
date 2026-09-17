<?php
/**
 * Cart API. JSON in, JSON out. Also accepts a normal form POST (no JavaScript)
 * when "redirect" is set, in which case it sends the visitor to the cart page.
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$data   = $method === 'POST' ? request_json() : $_GET;
$action = (string) ($data['action'] ?? 'get');
$wantsRedirect = !empty($data['redirect']);

if ($method === 'POST' && !csrf_check($data['csrf'] ?? null)) {
    if ($wantsRedirect) {
        flash('error', 'Your session expired. Please try again.');
        redirect(route('cart'));
    }
    json_response(['ok' => false, 'error' => 'Your session expired. Reload the page and try again.'], 419);
}

$error = null;
switch ($action) {
    case 'add':
        $res = cart_add((int) ($data['product_id'] ?? 0), (string) ($data['grind'] ?? ''), (int) ($data['qty'] ?? 1));
        if (!$res['ok']) {
            $error = $res['error'];
        }
        break;
    case 'update':
        cart_update((string) ($data['key'] ?? ''), (int) ($data['qty'] ?? 1));
        break;
    case 'remove':
        cart_remove((string) ($data['key'] ?? ''));
        break;
    case 'clear':
        cart_clear();
        break;
    case 'get':
    default:
        break;
}

if ($wantsRedirect) {
    if ($error) {
        flash('error', $error);
    } else {
        flash('success', 'Added to your bag.');
    }
    redirect(route('cart'));
}

if ($error) {
    json_response(['ok' => false, 'error' => $error, 'cart' => cart_payload()], 422);
}
json_response(['ok' => true, 'cart' => cart_payload()]);
