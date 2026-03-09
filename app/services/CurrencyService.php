<?php
/**
 * CurrencyService - fetch exchange rates & caching
 */
class CurrencyService {
    private ExchangeRate $exchangeRateModel;

    public function __construct() {
        $this->exchangeRateModel = new ExchangeRate();
    }

    /**
     * Get exchange rate from source to target currency
     */
    public function getRate(string $from, string $to): float {
        if ($from === $to) return 1.0;

        // Try from DB first (cached)
        $rate = $this->exchangeRateModel->getRate($from, $to);
        if ($rate !== null) {
            return $rate;
        }

        // Fetch from API
        $rate = $this->fetchRateFromApi($from, $to);
        if ($rate !== null) {
            $this->exchangeRateModel->saveRate($from, $to, $rate);
            return $rate;
        }

        // Try inverse
        $inverseRate = $this->exchangeRateModel->getRate($to, $from);
        if ($inverseRate !== null && $inverseRate > 0) {
            return round(1 / $inverseRate, 8);
        }

        throw new \RuntimeException("Exchange rate not available for $from → $to");
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to): array {
        $rate = $this->getRate($from, $to);
        return [
            'amount'        => $amount,
            'from'          => $from,
            'to'            => $to,
            'rate'          => $rate,
            'converted'     => round($amount * $rate, 2),
        ];
    }

    /**
     * Fetch rates from external API
     */
    public function fetchRateFromApi(string $base, string $target): ?float {
        $url = EXCHANGE_API_URL;
        $params = ['base' => $base, 'symbols' => $target];
        if (EXCHANGE_API_KEY) {
            $params['access_key'] = EXCHANGE_API_KEY;
        }
        $url .= '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method'  => 'GET',
                'header'  => "Accept: application/json\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $data = json_decode($response, true);
        if (!$data || empty($data['rates'][$target])) {
            // Try alternate response format
            if (!empty($data['conversion_rates'][$target])) {
                return (float) $data['conversion_rates'][$target];
            }
            return null;
        }

        return (float) $data['rates'][$target];
    }

    /**
     * Fetch all rates for base currency and save to DB (for cron job)
     */
    public function fetchAndSaveAllRates(string $baseCurrency = null): int {
        $base = $baseCurrency ?? BASE_CURRENCY;
        $url = EXCHANGE_API_URL;
        $params = ['base' => $base];
        if (EXCHANGE_API_KEY) {
            $params['access_key'] = EXCHANGE_API_KEY;
        }
        $url .= '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => ['timeout' => 15, 'method' => 'GET', 'header' => "Accept: application/json\r\n"],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return 0;

        $data = json_decode($response, true);
        $rates = $data['rates'] ?? $data['conversion_rates'] ?? [];
        if (empty($rates)) return 0;

        $count = 0;
        foreach ($rates as $target => $rate) {
            if ($target === $base) continue;
            $this->exchangeRateModel->saveRate($base, $target, (float) $rate);
            $count++;
        }

        return $count;
    }
}
