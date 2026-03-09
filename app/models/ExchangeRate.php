<?php
class ExchangeRate extends Model {
    protected string $table = 'exchange_rates';

    public function getRate(string $from, string $to): ?float {
        if ($from === $to) return 1.0;

        $stmt = $this->db->prepare("
            SELECT rate FROM exchange_rates
            WHERE base_currency = :base AND target_currency = :target
            ORDER BY fetched_at DESC LIMIT 1
        ");
        $stmt->execute(['base' => $from, 'target' => $to]);
        $rate = $stmt->fetchColumn();
        return $rate !== false ? (float) $rate : null;
    }

    public function saveRate(string $base, string $target, float $rate): void {
        $stmt = $this->db->prepare("
            INSERT INTO exchange_rates (base_currency, target_currency, rate, fetched_at)
            VALUES (:base, :target, :rate, NOW())
        ");
        $stmt->execute(['base' => $base, 'target' => $target, 'rate' => $rate]);
    }

    public function getLatestRates(string $baseCurrency): array {
        $stmt = $this->db->prepare("
            SELECT er.* FROM exchange_rates er
            INNER JOIN (
                SELECT base_currency, target_currency, MAX(fetched_at) AS max_fetched
                FROM exchange_rates
                WHERE base_currency = :base
                GROUP BY base_currency, target_currency
            ) latest ON er.base_currency = latest.base_currency
                     AND er.target_currency = latest.target_currency
                     AND er.fetched_at = latest.max_fetched
            ORDER BY er.target_currency
        ");
        $stmt->execute(['base' => $baseCurrency]);
        return $stmt->fetchAll();
    }
}
