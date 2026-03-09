<?php
class Payment extends Model {
    protected string $table = 'payments';

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO payments
                (invoice_id, currency, amount, amount_in_base, exchange_rate, provider,
                 provider_payment_id, idempotency_key, status, payment_method, payment_date, notes, raw_response)
            VALUES
                (:invoice_id, :currency, :amount, :amount_in_base, :exchange_rate, :provider,
                 :provider_payment_id, :idempotency_key, :status, :payment_method, :payment_date, :notes, :raw_response)
        ");
        $stmt->execute([
            'invoice_id'          => $data['invoice_id'],
            'currency'            => $data['currency'] ?? BASE_CURRENCY,
            'amount'              => $data['amount'],
            'amount_in_base'      => $data['amount_in_base'] ?? $data['amount'],
            'exchange_rate'       => $data['exchange_rate'] ?? null,
            'provider'            => $data['provider'] ?? 'manual',
            'provider_payment_id' => $data['provider_payment_id'] ?? null,
            'idempotency_key'     => $data['idempotency_key'] ?? null,
            'status'              => $data['status'] ?? 'pending',
            'payment_method'      => $data['payment_method'] ?? null,
            'payment_date'        => $data['payment_date'] ?? date('Y-m-d H:i:s'),
            'notes'               => $data['notes'] ?? null,
            'raw_response'        => isset($data['raw_response']) ? json_encode($data['raw_response']) : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByInvoice(int $invoiceId): array {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE invoice_id = :id ORDER BY created_at DESC");
        $stmt->execute(['id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    public function findByProviderPaymentId(string $providerId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE provider_payment_id = :pid LIMIT 1");
        $stmt->execute(['pid' => $providerId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByIdempotencyKey(string $key): ?array {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE idempotency_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE payments SET status = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
