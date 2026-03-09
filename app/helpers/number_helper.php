<?php
/**
 * Number & currency formatting helpers
 */

/**
 * Format angka sesuai currency
 */
function format_currency(float $amount, string $currencyCode = 'IDR'): string {
    $symbols = [
        'IDR' => 'Rp', 'USD' => '$', 'EUR' => '€', 'SGD' => 'S$',
        'MYR' => 'RM', 'JPY' => '¥', 'GBP' => '£', 'AUD' => 'A$',
    ];
    $symbol = $symbols[$currencyCode] ?? $currencyCode . ' ';

    // IDR and JPY have no decimal places typically
    $decimals = in_array($currencyCode, ['IDR', 'JPY']) ? 0 : 2;
    $formatted = number_format($amount, $decimals, ',', '.');

    return $symbol . ' ' . $formatted;
}

/**
 * Konversi amount dari satu currency ke currency lain
 */
function convert_currency(float $amount, float $exchangeRate): float {
    return round($amount * $exchangeRate, 2);
}

/**
 * Hitung subtotal item: quantity * unit_price
 */
function calculate_item_amount(float $quantity, float $unitPrice): float {
    return round($quantity * $unitPrice, 2);
}

/**
 * Hitung total invoice
 */
function calculate_invoice_total(float $subtotal, float $taxRate = 0, float $discountAmount = 0): array {
    $taxAmount = round($subtotal * ($taxRate / 100), 2);
    $total = round($subtotal + $taxAmount - $discountAmount, 2);
    return [
        'subtotal'        => $subtotal,
        'tax_rate'        => $taxRate,
        'tax_amount'      => $taxAmount,
        'discount_amount' => $discountAmount,
        'total'           => max(0, $total),
    ];
}

/**
 * Format tanggal Indonesia
 */
function format_date(string $date): string {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $ts = strtotime($date);
    $d = (int)date('j', $ts);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);
    return "$d {$months[$m]} $y";
}

/**
 * Generate secure random idempotency key
 */
function generate_idempotency_key(): string {
    return bin2hex(random_bytes(16));
}

/**
 * Sanitize string for output
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
