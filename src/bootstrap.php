<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$configFile = BASE_PATH . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing config.php — copy config.example.php to config.php and edit it.');
}
$GLOBALS['CONFIG'] = require $configFile;
date_default_timezone_set('Africa/Cairo');

function config(string $key, $default = null)
{
    $value = $GLOBALS['CONFIG'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

// ---------- Base URL (supports running in a subfolder) ----------
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDir = preg_replace('#/public$#', '', rtrim($scriptDir, '/'));
define('BASE_URL', $scriptDir === '/' ? '' : $scriptDir);
/** BASE_URL encoded for use inside links (spaces -> %20). */
define('BASE_HREF', implode('/', array_map('rawurlencode', explode('/', BASE_URL))));

// ---------- Session (hardened) ----------
if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli') {
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL === '' ? '/' : BASE_HREF . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('mkw_session');
    session_start();
}

require __DIR__ . '/db.php';
require __DIR__ . '/i18n.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/access.php';
require __DIR__ . '/billing.php';

// Never show stack traces (or database details) to visitors
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    set_exception_handler(function (Throwable $ex) {
        error_log((string)$ex);
        if (!headers_sent()) http_response_code(500);
        // Setup problems get a clear hint; anything else stays generic unless 'debug' is on in config.php
        $code = $ex instanceof PDOException ? (string)($ex->errorInfo[1] ?? $ex->getCode()) : '';
        $msg = match (true) {
            in_array($code, ['1045', '1044', '1049', '2002', '2005'], true)
                => 'Cannot connect to the database. Check the db settings (name, user, password, host) in config.php.',
            $code === '1146' || $code === '42S02'
                => 'The database tables are missing. Set install_key in config.php, then open /install to create them.',
            default => 'Something went wrong. Please try again in a moment.',
        };
        if (config('debug', false)) {
            $msg .= "\n\n" . $ex->getMessage() . "\n" . $ex->getFile() . ':' . $ex->getLine();
        }
        echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<pre style="font-family:sans-serif;padding:2rem;white-space:pre-wrap">' . htmlspecialchars($msg) . '</pre>';
    });
}

// Security headers for every response
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com; style-src-attr 'unsafe-inline'; font-src https://fonts.gstatic.com; script-src 'self'; form-action 'self' tel:; frame-ancestors 'none'");
}
