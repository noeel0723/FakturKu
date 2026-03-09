<?php
/**
 * Cron job: Fetch exchange rates daily
 * Usage: php cron/fetch_rates.php
 * Crontab: 0 6 * * * php /path/to/FakturKu/cron/fetch_rates.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/helpers/number_helper.php';
require_once BASE_PATH . '/app/core/Database.php';
require_once BASE_PATH . '/app/core/Model.php';
require_once BASE_PATH . '/app/models/ExchangeRate.php';
require_once BASE_PATH . '/app/services/CurrencyService.php';

echo "[" . date('Y-m-d H:i:s') . "] Fetching exchange rates...\n";

try {
    $service = new CurrencyService();
    $count = $service->fetchAndSaveAllRates(BASE_CURRENCY);
    echo "[" . date('Y-m-d H:i:s') . "] Saved $count exchange rates for base currency: " . BASE_CURRENCY . "\n";
} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Done.\n";
