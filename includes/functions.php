<?php
/**
 * Shared helpers: sessions, escaping, URLs, settings, products, cart, orders, mail.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (BASE_URL === '' ? '/' : BASE_URL . '/'),
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
start_session();

// ---------------------------------------------------------------------------
// Output helpers
// ---------------------------------------------------------------------------
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape and turn double line breaks into paragraphs. */
function paragraphs(?string $text): string
{
    $parts = preg_split('/\R{2,}/', trim((string) $text)) ?: [];
    $out = '';
    foreach ($parts as $p) {
        if (trim($p) === '') {
            continue;
        }
        $out .= '<p>' . nl2br(e($p)) . '</p>';
    }
    return $out;
}

function money(int $cents, bool $withSymbol = true): string
{
    $symbol = $withSymbol ? setting('currency_symbol', 'R') : '';
    return $symbol . number_format($cents / 100, 2, '.', ' ');
}

function url(string $path = ''): string
{
    return (BASE_URL === '' ? '' : BASE_URL) . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = dirname(__DIR__) . '/' . ltrim($path, '/');
    $v    = file_exists($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $v;
}

function site_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

/** Named routes; pretty URLs when the setting is on and .htaccess is active. */
function route(string $name, $arg = null, array $query = []): string
{
    $pretty = setting('pretty_urls', '0') === '1';
    switch ($name) {
        case 'home':     $path = $pretty ? '' : 'index.php'; break;
        case 'product':
            $path = $pretty ? 'product/' . rawurlencode((string) $arg) : 'product.php';
            if (!$pretty) { $query = ['slug' => $arg] + $query; }
            break;
        case 'order':
            $path = $pretty ? 'order/' . rawurlencode((string) $arg) : 'order.php';
            if (!$pretty) { $query = ['ref' => $arg] + $query; }
            break;
        default:
            $path = $pretty ? $name : $name . '.php';
    }
    $qs = $query ? '?' . http_build_query($query) : '';
    return url($path) . $qs;
}

function current_page(): string
{
    return basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '.php');
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function request_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

// ---------------------------------------------------------------------------
// CSRF and flash messages
// ---------------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(20));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
function settings_all(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function setting_int(string $key, int $default = 0): int
{
    $v = setting($key, (string) $default);
    return is_numeric($v) ? (int) $v : $default;
}

function settings_save(array $pairs): void
{
    $st = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    foreach ($pairs as $k => $v) {
        $st->execute([$k, (string) $v]);
    }
}

// ---------------------------------------------------------------------------
// Products, grinds, testimonials
// ---------------------------------------------------------------------------
function products_active(): array
{
    return db()->query('SELECT * FROM products WHERE active = 1 ORDER BY sort ASC, id ASC')->fetchAll();
}

function products_all(): array
{
    return db()->query('SELECT * FROM products ORDER BY sort ASC, id ASC')->fetchAll();
}

function product_by_slug(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM products WHERE slug = ? AND active = 1');
    $st->execute([$slug]);
    $p = $st->fetch();
    return $p ?: null;
}

function product_by_id(int $id, bool $activeOnly = true): ?array
{
    $st = db()->prepare('SELECT * FROM products WHERE id = ?' . ($activeOnly ? ' AND active = 1' : ''));
    $st->execute([$id]);
    $p = $st->fetch();
    return $p ?: null;
}

function products_in_group(string $group): array
{
    if ($group === '') {
        return [];
    }
    $st = db()->prepare('SELECT * FROM products WHERE group_key = ? AND active = 1 ORDER BY weight_g ASC, sort ASC');
    $st->execute([$group]);
    return $st->fetchAll();
}

/** The coffee shown in the homepage profile section: first product of the featured group. */
function featured_product(): ?array
{
    $group = setting('featured_group');
    $list  = $group !== '' ? products_in_group($group) : [];
    if (!$list) {
        $list = products_active();
    }
    return $list[0] ?? null;
}

function grinds_active(): array
{
    return db()->query('SELECT * FROM grinds WHERE active = 1 ORDER BY sort ASC, id ASC')->fetchAll();
}

function grind_by_key(string $key): ?array
{
    $st = db()->prepare('SELECT * FROM grinds WHERE key = ? AND active = 1');
    $st->execute([$key]);
    $g = $st->fetch();
    return $g ?: null;
}

function testimonials_active(): array
{
    return db()->query('SELECT * FROM testimonials WHERE active = 1 ORDER BY sort ASC, id ASC')->fetchAll();
}

function notes_list(?string $notes): array
{
    $parts = array_map('trim', explode(',', (string) $notes));
    return array_values(array_filter($parts, fn($n) => $n !== ''));
}

function price_per_100g(array $p): string
{
    $w = max(1, (int) $p['weight_g']);
    return money((int) round($p['price_cents'] / $w * 100));
}

function product_image_url(array $p): ?string
{
    if (!empty($p['image'])) {
        return url('uploads/' . rawurlencode($p['image']));
    }
    return null;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

// ---------------------------------------------------------------------------
// Cart (server side session, priced live from the database)
// ---------------------------------------------------------------------------
function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_key(int $productId, string $grind): string
{
    return $productId . '|' . $grind;
}

function cart_add(int $productId, string $grind, int $qty): array
{
    $product = product_by_id($productId);
    if (!$product) {
        return ['ok' => false, 'error' => 'That product is no longer available.'];
    }
    $g = grind_by_key($grind);
    if (!$g) {
        return ['ok' => false, 'error' => 'Please choose a grind.'];
    }
    $qty = max(1, min(50, $qty));
    $key = cart_key($productId, $grind);
    $current = $_SESSION['cart'][$key]['qty'] ?? 0;
    $new = $current + $qty;
    if ((int) $product['stock'] >= 0 && $new > (int) $product['stock']) {
        $new = (int) $product['stock'];
        if ($new <= 0) {
            return ['ok' => false, 'error' => 'Sorry, that size is sold out.'];
        }
    }
    $_SESSION['cart'][$key] = ['product_id' => $productId, 'grind' => $grind, 'qty' => $new];
    return ['ok' => true];
}

function cart_update(string $key, int $qty): void
{
    if (!isset($_SESSION['cart'][$key])) {
        return;
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
        return;
    }
    $p = product_by_id((int) $_SESSION['cart'][$key]['product_id']);
    if ($p && (int) $p['stock'] >= 0) {
        $qty = min($qty, (int) $p['stock']);
    }
    $_SESSION['cart'][$key]['qty'] = max(1, min(50, $qty));
}

function cart_remove(string $key): void
{
    unset($_SESSION['cart'][$key]);
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

/** Fully hydrated cart with live prices and totals. */
function cart(): array
{
    $items = [];
    $subtotal = 0;
    $count = 0;
    foreach (cart_raw() as $key => $line) {
        $p = product_by_id((int) $line['product_id']);
        $g = grind_by_key((string) $line['grind']);
        if (!$p || !$g) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $qty  = (int) $line['qty'];
        $line_cents = $qty * (int) $p['price_cents'];
        $subtotal += $line_cents;
        $count += $qty;
        $items[] = [
            'key'         => $key,
            'product'     => $p,
            'grind'       => $g,
            'qty'         => $qty,
            'unit_cents'  => (int) $p['price_cents'],
            'line_cents'  => $line_cents,
        ];
    }
    $threshold = setting_int('free_shipping_threshold_cents', 50000);
    $fee       = setting_int('shipping_fee_cents', 7900);
    $shipping  = ($subtotal >= $threshold || $subtotal === 0) ? 0 : $fee;
    return [
        'items'          => $items,
        'count'          => $count,
        'subtotal_cents' => $subtotal,
        'shipping_cents' => $shipping,
        'total_cents'    => $subtotal + $shipping,
        'threshold_cents'=> $threshold,
        'to_free_cents'  => max(0, $threshold - $subtotal),
        'fee_cents'      => $fee,
    ];
}

/** JSON friendly cart for the front end. */
function cart_payload(): array
{
    $c = cart();
    $items = [];
    foreach ($c['items'] as $it) {
        $p = $it['product'];
        $items[] = [
            'key'        => $it['key'],
            'id'         => (int) $p['id'],
            'slug'       => $p['slug'],
            'name'       => $p['name'],
            'size'       => $p['size_label'],
            'accent'     => $p['accent'],
            'image'      => product_image_url($p),
            'grind'      => $it['grind']['name'],
            'grind_key'  => $it['grind']['key'],
            'qty'        => $it['qty'],
            'unit'       => money($it['unit_cents']),
            'line'       => money($it['line_cents']),
            'url'        => route('product', $p['slug']),
            'max'        => (int) $p['stock'] >= 0 ? (int) $p['stock'] : 50,
        ];
    }
    return [
        'items'     => $items,
        'count'     => $c['count'],
        'subtotal'  => money($c['subtotal_cents']),
        'shipping'  => $c['shipping_cents'] === 0 ? 'Free' : money($c['shipping_cents']),
        'total'     => money($c['total_cents']),
        'to_free'   => $c['to_free_cents'] > 0 ? money($c['to_free_cents']) : '',
        'progress'  => $c['threshold_cents'] > 0 ? min(100, (int) round($c['subtotal_cents'] / $c['threshold_cents'] * 100)) : 100,
        'checkout'  => route('checkout'),
        'cart_url'  => route('cart'),
    ];
}

// ---------------------------------------------------------------------------
// Orders
// ---------------------------------------------------------------------------
function order_statuses(): array
{
    return [
        'pending_payment'      => 'Awaiting payment',
        'paid'                 => 'Paid',
        'roasting'             => 'Roasting',
        'shipped'              => 'Shipped',
        'ready_for_collection' => 'Ready for collection',
        'completed'            => 'Completed',
        'cancelled'            => 'Cancelled',
    ];
}

function status_label(string $status): string
{
    return order_statuses()[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function provinces(): array
{
    return ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape'];
}

function order_ref(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $ref = '';
    for ($i = 0; $i < 6; $i++) {
        $ref .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return 'CGS-' . $ref;
}

function order_by_ref(string $ref, ?string $key = null): ?array
{
    $st = db()->prepare('SELECT * FROM orders WHERE ref = ?');
    $st->execute([$ref]);
    $o = $st->fetch();
    if (!$o) {
        return null;
    }
    if ($key !== null && !hash_equals($o['access_key'], $key)) {
        return null;
    }
    $it = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $it->execute([$o['id']]);
    $o['items'] = $it->fetchAll();
    return $o;
}

function order_by_id(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$id]);
    $o = $st->fetch();
    if (!$o) {
        return null;
    }
    $it = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $it->execute([$o['id']]);
    $o['items'] = $it->fetchAll();
    return $o;
}

function order_set_status(int $id, string $status, string $paymentRef = ''): void
{
    if (!isset(order_statuses()[$status])) {
        return;
    }
    $sql = 'UPDATE orders SET status = ?, updated_at = datetime(\'now\')' . ($paymentRef !== '' ? ', payment_ref = ?' : '') . ' WHERE id = ?';
    $params = $paymentRef !== '' ? [$status, $paymentRef, $id] : [$status, $id];
    db()->prepare($sql)->execute($params);
}

/**
 * Create an order from the current cart. Returns the order row (with ref and key).
 */
function order_create(array $customer): array
{
    $c = cart();
    if (!$c['items']) {
        throw new RuntimeException('Your bag is empty.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ref = order_ref();
        $tries = 0;
        while ($tries++ < 5) {
            $chk = $pdo->prepare('SELECT 1 FROM orders WHERE ref = ?');
            $chk->execute([$ref]);
            if (!$chk->fetch()) {
                break;
            }
            $ref = order_ref();
        }
        $key = bin2hex(random_bytes(10));

        $shipping = $customer['delivery_method'] === 'collection' ? 0 : $c['shipping_cents'];
        $total    = $c['subtotal_cents'] + $shipping;

        $st = $pdo->prepare('INSERT INTO orders (ref, access_key, status, email, phone, first_name, last_name, address1, address2, suburb, city, province, postcode, delivery_method, payment_method, notes, subtotal_cents, shipping_cents, total_cents)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([
            $ref, $key, 'pending_payment',
            $customer['email'], $customer['phone'], $customer['first_name'], $customer['last_name'],
            $customer['address1'], $customer['address2'], $customer['suburb'], $customer['city'], $customer['province'], $customer['postcode'],
            $customer['delivery_method'], $customer['payment_method'], $customer['notes'],
            $c['subtotal_cents'], $shipping, $total,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $li = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, size_label, grind_key, grind_name, unit_cents, qty, line_cents) VALUES (?,?,?,?,?,?,?,?,?)');
        $stock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= 0');
        foreach ($c['items'] as $it) {
            $p = $it['product'];
            $li->execute([$orderId, (int) $p['id'], $p['name'], $p['size_label'], $it['grind']['key'], $it['grind']['name'], $it['unit_cents'], $it['qty'], $it['line_cents']]);
            $stock->execute([$it['qty'], (int) $p['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    cart_clear();
    $order = order_by_id($orderId);
    send_order_email($order);
    return $order;
}

// ---------------------------------------------------------------------------
// Mail (falls back to a log file so local development never breaks)
// ---------------------------------------------------------------------------
function send_mail(string $to, string $subject, string $body): bool
{
    $from = setting('mail_from', 'orders@example.com');
    $headers = "From: " . setting('store_name') . " <{$from}>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    if (setting('mail_enabled') === '1' && function_exists('mail')) {
        return @mail($to, $subject, $body, $headers);
    }
    $log = dirname(DB_PATH) . '/mail.log';
    $entry = "==== " . date('Y-m-d H:i:s') . " to: {$to}\nSubject: {$subject}\n\n{$body}\n\n";
    @file_put_contents($log, $entry, FILE_APPEND);
    return true;
}

function send_order_email(array $order): void
{
    $lines = [];
    foreach ($order['items'] as $it) {
        $lines[] = sprintf('%d x %s %s (%s)  %s', $it['qty'], $it['product_name'], $it['size_label'], $it['grind_name'], money((int) $it['line_cents']));
    }
    $body = "Hi {$order['first_name']},\n\nThank you for your order {$order['ref']}.\n\n"
        . implode("\n", $lines) . "\n\n"
        . 'Delivery: ' . ($order['shipping_cents'] > 0 ? money((int) $order['shipping_cents']) : 'Free') . "\n"
        . 'Total: ' . money((int) $order['total_cents']) . "\n\n";
    if ($order['payment_method'] === 'eft') {
        $body .= "Please pay by EFT using your order number as the reference:\n"
            . setting('eft_bank_name') . "\n" . setting('eft_account_name') . "\nAccount: " . setting('eft_account_number') . "\nBranch: " . setting('eft_branch_code') . "\n\n";
    }
    $body .= "You can view your order here:\n" . site_url(ltrim(route('order', $order['ref'], ['key' => $order['access_key']]), '/')) . "\n\n" . setting('store_name');
    send_mail($order['email'], 'Your order ' . $order['ref'], $body);
}

// ---------------------------------------------------------------------------
// PayFast
// ---------------------------------------------------------------------------
function payfast_enabled(): bool
{
    return setting('payfast_enabled') === '1' && setting('payfast_merchant_id') !== '' && setting('payfast_merchant_key') !== '';
}

function payfast_signature(array $data, string $passPhrase = ''): string
{
    $out = '';
    foreach ($data as $key => $val) {
        if ($val !== '' && $val !== null) {
            $out .= $key . '=' . urlencode(trim((string) $val)) . '&';
        }
    }
    $str = substr($out, 0, -1);
    if ($passPhrase !== '') {
        $str .= '&passphrase=' . urlencode(trim($passPhrase));
    }
    return md5($str);
}

/** Fields for the PayFast redirect form, in the order PayFast expects. */
function payfast_fields(array $order): array
{
    $data = [
        'merchant_id'      => setting('payfast_merchant_id'),
        'merchant_key'     => setting('payfast_merchant_key'),
        'return_url'       => site_url(ltrim(route('order', $order['ref'], ['key' => $order['access_key'], 'pf' => 'return']), '/')),
        'cancel_url'       => site_url(ltrim(route('order', $order['ref'], ['key' => $order['access_key'], 'pf' => 'cancel']), '/')),
        'notify_url'       => site_url('api/payfast-itn.php'),
        'name_first'       => $order['first_name'],
        'name_last'        => $order['last_name'],
        'email_address'    => $order['email'],
        'm_payment_id'     => $order['ref'],
        'amount'           => number_format($order['total_cents'] / 100, 2, '.', ''),
        'item_name'        => setting('store_name') . ' order ' . $order['ref'],
        'custom_str1'      => $order['access_key'],
    ];
    $data['signature'] = payfast_signature($data, setting('payfast_passphrase'));
    return $data;
}

function payfast_process_url(): string
{
    return setting('payfast_sandbox', '1') === '1' ? 'https://sandbox.payfast.co.za/eng/process' : 'https://www.payfast.co.za/eng/process';
}
