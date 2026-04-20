<?php
/**
 * config.php — Aero Greet India
 * Edit DB_* and SMTP_* constants to match your cPanel hosting.
 * This file is auto-written by install.php — you can also edit manually.
 */

// ── Database ────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'YOUR_DB_NAME');   // e.g.  cpanelusername_aerogreet
define('DB_USER',    'YOUR_DB_USER');   // e.g.  cpanelusername_dbuser
define('DB_PASS',    'YOUR_DB_PASS');
define('DB_CHARSET', 'utf8mb4');

// ── Site ────────────────────────────────────────────────────────
define('SITE_URL',   'https://aerogreetindia.com');
define('SITE_NAME',  'Aero Greet India');

// ── Email ────────────────────────────────────────────────────────
define('SMTP_HOST',  'mail.aerogreetindia.com');
define('SMTP_PORT',  587);
define('SMTP_USER',  'hello@aerogreetindia.com');
define('SMTP_PASS',  'Hello@3490');
define('MAIL_FROM',  'hello@aerogreetindia.com');
define('MAIL_NAME',  'Aero Greet India');
define('ADMIN_TO',   'sales@aerogreetindia.com,admin@travelblooper.com');

// ── Security ────────────────────────────────────────────────────
define('APP_SECRET',  'CHANGE_THIS_64CHAR_RANDOM_STRING_aerogreetindia_2025_secure_key');
define('SESSION_TTL',  7200);   // 2 hours
define('RATE_LIMIT',   20);     // per hour per IP

// ── DB availability flag ─────────────────────────────────────────
define('DB_CONFIGURED', DB_NAME !== 'YOUR_DB_NAME' && DB_NAME !== '');

// ── PDO singleton ───────────────────────────────────────────────
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES=>false]
        );
    }
    return $pdo;
}

// ── Safe db() — returns null instead of dying ───────────────────
function dbSafe(): ?PDO {
    static $pdo, $tried = false;
    if ($tried) return $pdo;
    $tried = true;
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES=>false]
        );
    } catch (PDOException $e) {
        $pdo = null;
    }
    return $pdo;
}

// ── Override db() to use safe version ───────────────────────────
// Re-declare db() to NOT die — just throw so callers can catch
