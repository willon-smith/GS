<?php
/**
 * Commodities Good Steward: site configuration.
 *
 * Most settings live in the database and are edited from the admin panel
 * (Settings). This file only holds what the app needs before the database
 * exists, plus a few developer switches.
 *
 * To override anything here without touching version control, create
 * includes/config.local.php and define the constants there first.
 */

declare(strict_types=1);

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Environment variables take precedence over the defaults (used by Docker and Render).
$env = static fn(string $key, string $default): string => (($v = getenv($key)) !== false && $v !== '') ? $v : $default;

defined('APP_ENV')        || define('APP_ENV', $env('APP_ENV', 'development'));   // development | production
defined('APP_DEBUG')      || define('APP_DEBUG', APP_ENV === 'development');
defined('DB_PATH')        || define('DB_PATH', $env('DB_PATH', dirname(__DIR__) . '/data/cgs.sqlite'));
defined('UPLOAD_DIR')     || define('UPLOAD_DIR', $env('UPLOAD_DIR', dirname(__DIR__) . '/uploads'));
defined('SESSION_NAME')   || define('SESSION_NAME', 'cgs_session');
defined('ADMIN_DEFAULT_USER')     || define('ADMIN_DEFAULT_USER', $env('ADMIN_DEFAULT_USER', 'admin'));
defined('ADMIN_DEFAULT_PASSWORD') || define('ADMIN_DEFAULT_PASSWORD', $env('ADMIN_DEFAULT_PASSWORD', 'stewardship')); // seeded once, change it in Admin > Settings > Account
unset($env);

// Behind a proxy that terminates TLS (Render, Cloudflare, most hosts) the request
// arrives as plain HTTP with this header set; treat it as HTTPS so generated
// links and the session cookie are correct.
if (empty($_SERVER['HTTPS']) && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) {
    $_SERVER['HTTPS'] = 'on';
}

// ---------------------------------------------------------------------------
// Base URL detection. Works at http://localhost/Websites/Commodities%20Good%20Steward/
// under WAMP, at the root of a virtual host, and under PHP's built-in server.
// ---------------------------------------------------------------------------
if (!defined('BASE_URL')) {
    $docRoot  = str_replace('\\', '/', rtrim((string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
    $siteRoot = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
    $base = '';
    if ($docRoot !== '' && stripos($siteRoot, $docRoot) === 0) {
        $base = substr($siteRoot, strlen($docRoot));
    }
    $base = implode('/', array_map('rawurlencode', explode('/', $base)));
    define('BASE_URL', rtrim($base, '/'));
}

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

date_default_timezone_set('Africa/Johannesburg');
mb_internal_encoding('UTF-8');
