<?php
/**
 * mpesa_config.php
 * Loads .env and exposes M-Pesa Daraja API configuration constants.
 */

function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and blank lines
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load .env from project root
loadEnv(__DIR__ . '/.env');

// ── Daraja credentials ────────────────────────────────────────────────────────
define('MPESA_ENV',            getenv('MPESA_ENV')            ?: 'sandbox');
define('MPESA_CONSUMER_KEY',   getenv('MPESA_CONSUMER_KEY')   ?: '');
define('MPESA_CONSUMER_SECRET',getenv('MPESA_CONSUMER_SECRET')?: '');
define('MPESA_SHORTCODE',      getenv('MPESA_SHORTCODE')      ?: '174379');
define('MPESA_PASSKEY',        getenv('MPESA_PASSKEY')        ?: '');
define('MPESA_CALLBACK_URL',   getenv('MPESA_CALLBACK_URL')   ?: '');
define('MPESA_ACCOUNT_REF',    getenv('MPESA_ACCOUNT_REF')    ?: 'GlobalHospitals');

// ── Base URL (sandbox vs production) ─────────────────────────────────────────
define('MPESA_BASE_URL', MPESA_ENV === 'production'
    ? 'https://api.safaricom.co.ke'
    : 'https://sandbox.safaricom.co.ke');
