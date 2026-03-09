<?php
class Invoice extends Model {
    protected string $table = 'invoices';

    /**
     * Generate unique invoice number atomically
     * Format: INV-2026/03-0001
     */
    public function generateNumber(): string {
        $prefix = INVOICE_PREFIX;
        $year   = (int) date('Y');
        $month  = (int) date('m');

        // Atomic increment with INSERT ... ON DUPLICATE KEY UPDATE
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO invoice_sequences (prefix, year, month, last_number)
                VALUES (:prefix, :year, :month, 1)
                ON DUPLICATE KEY UPDATE last_number = last_number + 1
            ");
            $stmt->execute(['prefix' => $prefix, 'year' => $year, 'month' => $month]);

            $stmt = $this->db->prepare("
                SELECT last_number FROM invoice_sequences
                WHERE prefix = :prefix AND year = :year AND month = :month
            ");
            $stmt->execute(['prefix' => $prefix, 'year' => $year, 'month' => $month]);
            $num = (int) $stmt->fetchColumn();

            $this->db->commit();
        } catch (\Exception $ex) {
            $this->db->rollBack();
            throw $ex;
        }

        return sprintf('%s-%04d/%02d-%04d', $prefix, $year, $month, $num);
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO invoices
                (invoice_number, client_id, currency, exchange_rate, subtotal, tax_rate, tax_amount,
                 discount_amount, total, total_in_base, status, issue_date, due_date, notes)
            VALUES
                (:invoice_number, :client_id, :currency, :exchange_rate, :subtotal, :tax_rate, :tax_amount,
                 :discount_amount, :total, :total_in_base, :status, :issue_date, :due_date, :notes)
        ");
        $stmt->execute([
            'invoice_number'  => $data['invoice_number'],
            'client_id'       => $data['client_id'],
            'currency'        => $data['currency'] ?? BASE_CURRENCY,
            'exchange_rate'   => $data['exchange_rate'] ?? null,
            'subtotal'        => $data['subtotal'],
            'tax_rate'        => $data['tax_rate'] ?? 0,
            'tax_amount'      => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'total'           => $data['total'],
            'total_in_base'   => $data['total_in_base'] ?? null,
            'status'          => $data['status'] ?? 'draft',
            'issue_date'      => $data['issue_date'],
            'due_date'        => $data['due_date'],
            'notes'           => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        foreach (['client_id','currency','exchange_rate','subtotal','tax_rate','tax_amount',
                   'discount_amount','total','total_in_base','status','issue_date','due_date','notes'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = :$f";
                $params[$f] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $sql = "UPDATE invoices SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function findWithClient(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT i.*, c.name AS client_name, c.email AS client_email,
                   c.phone AS client_phone, c.address AS client_address, c.company AS client_company
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            WHERE i.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAllWithClient(): array {
        $stmt = $this->db->query("
            SELECT i.*, c.name AS client_name
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            ORDER BY i.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function updateAmountPaid(int $id): void {
        $stmt = $this->db->prepare("
            UPDATE invoices SET
                amount_paid = (SELECT COALESCE(SUM(amount_in_base), 0) FROM payments WHERE invoice_id = :iid AND status = 'success'),
                status = CASE
                    WHEN (SELECT COALESCE(SUM(amount_in_base), 0) FROM payments WHERE invoice_id = :iid2 AND status = 'success') >= total_in_base THEN 'paid'
                    WHEN (SELECT COALESCE(SUM(amount_in_base), 0) FROM payments WHERE invoice_id = :iid3 AND status = 'success') > 0 THEN 'partially_paid'
                    ELSE status
                END
            WHERE id = :id
        ");
        $stmt->execute(['iid' => $id, 'iid2' => $id, 'iid3' => $id, 'id' => $id]);
    }

    public function getDashboardStats(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) AS total_invoices,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN status IN ('sent','partially_paid') THEN 1 ELSE 0 END) AS unpaid_count,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count,
                COALESCE(SUM(total_in_base), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN total_in_base ELSE 0 END), 0) AS paid_revenue,
                COALESCE(SUM(CASE WHEN status IN ('sent','partially_paid','overdue') THEN total_in_base - COALESCE(amount_paid,0) ELSE 0 END), 0) AS outstanding
            FROM invoices
        ");
        return $stmt->fetch();
    }
}
