<?php
/**
 * Database connection, schema and first-run seeding.
 *
 * SQLite by default (zero setup). The schema is created on first request and
 * the template data is seeded once, so a fresh clone works immediately.
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $fresh = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    db_migrate($pdo);
    if ($fresh || (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0) {
        db_seed($pdo);
    }
    return $pdo;
}

function db_migrate(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS products (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    slug                TEXT NOT NULL UNIQUE,
    group_key           TEXT NOT NULL DEFAULT '',
    name                TEXT NOT NULL,
    tagline             TEXT NOT NULL DEFAULT '',
    description         TEXT NOT NULL DEFAULT '',
    origin              TEXT NOT NULL DEFAULT '',
    region              TEXT NOT NULL DEFAULT '',
    process             TEXT NOT NULL DEFAULT '',
    altitude            TEXT NOT NULL DEFAULT '',
    varietal            TEXT NOT NULL DEFAULT '',
    roast_level         INTEGER NOT NULL DEFAULT 3,
    acidity             INTEGER NOT NULL DEFAULT 3,
    body                INTEGER NOT NULL DEFAULT 3,
    sweetness           INTEGER NOT NULL DEFAULT 3,
    tasting_notes       TEXT NOT NULL DEFAULT '',
    weight_g            INTEGER NOT NULL DEFAULT 250,
    size_label          TEXT NOT NULL DEFAULT '250g',
    price_cents         INTEGER NOT NULL DEFAULT 0,
    compare_price_cents INTEGER NOT NULL DEFAULT 0,
    cups                INTEGER NOT NULL DEFAULT 0,
    badge               TEXT NOT NULL DEFAULT '',
    accent              TEXT NOT NULL DEFAULT '#C98B55',
    image               TEXT NOT NULL DEFAULT '',
    stock               INTEGER NOT NULL DEFAULT -1,
    active              INTEGER NOT NULL DEFAULT 1,
    sort                INTEGER NOT NULL DEFAULT 0,
    created_at          TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE IF NOT EXISTS grinds (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    key         TEXT NOT NULL UNIQUE,
    name        TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    brewers     TEXT NOT NULL DEFAULT '',
    icon        TEXT NOT NULL DEFAULT 'bean',
    active      INTEGER NOT NULL DEFAULT 1,
    sort        INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS orders (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    ref             TEXT NOT NULL UNIQUE,
    access_key      TEXT NOT NULL,
    status          TEXT NOT NULL DEFAULT 'pending_payment',
    email           TEXT NOT NULL,
    phone           TEXT NOT NULL DEFAULT '',
    first_name      TEXT NOT NULL,
    last_name       TEXT NOT NULL,
    address1        TEXT NOT NULL DEFAULT '',
    address2        TEXT NOT NULL DEFAULT '',
    suburb          TEXT NOT NULL DEFAULT '',
    city            TEXT NOT NULL DEFAULT '',
    province        TEXT NOT NULL DEFAULT '',
    postcode        TEXT NOT NULL DEFAULT '',
    delivery_method TEXT NOT NULL DEFAULT 'courier',
    payment_method  TEXT NOT NULL DEFAULT 'eft',
    notes           TEXT NOT NULL DEFAULT '',
    subtotal_cents  INTEGER NOT NULL DEFAULT 0,
    shipping_cents  INTEGER NOT NULL DEFAULT 0,
    total_cents     INTEGER NOT NULL DEFAULT 0,
    payment_ref     TEXT NOT NULL DEFAULT '',
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE IF NOT EXISTS order_items (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id     INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id   INTEGER NOT NULL,
    product_name TEXT NOT NULL,
    size_label   TEXT NOT NULL DEFAULT '',
    grind_key    TEXT NOT NULL DEFAULT '',
    grind_name   TEXT NOT NULL DEFAULT '',
    unit_cents   INTEGER NOT NULL,
    qty          INTEGER NOT NULL,
    line_cents   INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS subscribers (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    email      TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE IF NOT EXISTS messages (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL,
    subject    TEXT NOT NULL DEFAULT '',
    message    TEXT NOT NULL,
    is_read    INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE IF NOT EXISTS testimonials (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    name     TEXT NOT NULL,
    location TEXT NOT NULL DEFAULT '',
    quote    TEXT NOT NULL,
    rating   INTEGER NOT NULL DEFAULT 5,
    active   INTEGER NOT NULL DEFAULT 1,
    sort     INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS admin_users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_products_group ON products(group_key, sort);
SQL);
}

function db_seed(PDO $pdo): void
{
    require __DIR__ . '/seed.php'; // defines $SEED_SETTINGS, $SEED_PRODUCTS, $SEED_GRINDS, $SEED_TESTIMONIALS

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
        foreach ($SEED_SETTINGS as $k => $v) {
            $ins->execute([$k, (string) $v]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
            $cols = array_keys($SEED_PRODUCTS[0]);
            $sql  = 'INSERT INTO products (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
            $st   = $pdo->prepare($sql);
            foreach ($SEED_PRODUCTS as $p) {
                $st->execute(array_values($p));
            }
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM grinds')->fetchColumn() === 0) {
            $st = $pdo->prepare('INSERT INTO grinds (key, name, description, brewers, icon, active, sort) VALUES (?,?,?,?,?,1,?)');
            foreach ($SEED_GRINDS as $i => $g) {
                $st->execute([$g['key'], $g['name'], $g['description'], $g['brewers'], $g['icon'], $i]);
            }
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn() === 0) {
            $st = $pdo->prepare('INSERT INTO testimonials (name, location, quote, rating, active, sort) VALUES (?,?,?,?,1,?)');
            foreach ($SEED_TESTIMONIALS as $i => $t) {
                $st->execute([$t['name'], $t['location'], $t['quote'], $t['rating'], $i]);
            }
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 0) {
            $st = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
            $st->execute([ADMIN_DEFAULT_USER, password_hash(ADMIN_DEFAULT_PASSWORD, PASSWORD_DEFAULT)]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
