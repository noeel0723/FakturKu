<?php
/**
 * App Configuration - loads .env and sets constants
 */

// Load .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

function env(string $key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Database
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'fakturku'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// App
define('APP_NAME', env('APP_NAME', 'FakturKu'));
define('APP_URL', env('APP_URL', 'http://localhost/FakturKu/public'));
define('APP_ENV', env('APP_ENV', 'development'));

// Company
define('COMPANY_NAME', env('COMPANY_NAME', 'PT FakturKu'));
define('COMPANY_ADDRESS', env('COMPANY_ADDRESS', 'Jl. Contoh No. 123'));
define('COMPANY_PHONE', env('COMPANY_PHONE', '021-1234567'));
define('COMPANY_EMAIL', env('COMPANY_EMAIL', 'billing@fakturku.test'));

// Currency
define('BASE_CURRENCY', env('BASE_CURRENCY', 'IDR'));

// Payment
define('PAYMENT_PROVIDER', env('PAYMENT_PROVIDER', 'midtrans'));
define('STRIPE_SECRET', env('STRIPE_SECRET', ''));
define('STRIPE_PUBLISHABLE', env('STRIPE_PUBLISHABLE', ''));
define('STRIPE_WEBHOOK_SECRET', env('STRIPE_WEBHOOK_SECRET', ''));
define('MIDTRANS_SERVER_KEY', env('MIDTRANS_SERVER_KEY', ''));
define('MIDTRANS_CLIENT_KEY', env('MIDTRANS_CLIENT_KEY', ''));
define('MIDTRANS_IS_PRODUCTION', env('MIDTRANS_IS_PRODUCTION', 'false') === 'true');

// Exchange Rate API
define('EXCHANGE_API_URL', env('EXCHANGE_API_URL', 'https://api.exchangerate.host/latest'));
define('EXCHANGE_API_KEY', env('EXCHANGE_API_KEY', ''));

// Mail
define('MAIL_HOST', env('MAIL_HOST', 'smtp.mailtrap.io'));
define('MAIL_PORT', env('MAIL_PORT', '587'));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_FROM', env('MAIL_FROM', 'billing@fakturku.test'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'FakturKu'));

// Lightweight RBAC defaults
define('DEFAULT_USER_NAME', env('DEFAULT_USER_NAME', 'Owner User'));
define('DEFAULT_USER_ROLE', env('DEFAULT_USER_ROLE', 'owner'));

// Optional WhatsApp reminder connector
define('WHATSAPP_ENABLED', env('WHATSAPP_ENABLED', 'false') === 'true');
define('WHATSAPP_WEBHOOK_URL', env('WHATSAPP_WEBHOOK_URL', ''));

// Invoice prefix
define('INVOICE_PREFIX', env('INVOICE_PREFIX', 'INV'));

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));
