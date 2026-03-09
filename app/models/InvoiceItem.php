<?php
class InvoiceItem extends Model {
    protected string $table = 'invoice_items';

    public function createBatch(int $invoiceId, array $items): void {
        $stmt = $this->db->prepare("
            INSERT INTO invoice_items (invoice_id, product_id, description, quantity, unit, unit_price, amount, sort_order)
            VALUES (:invoice_id, :product_id, :description, :quantity, :unit, :unit_price, :amount, :sort_order)
        ");
        foreach ($items as $i => $item) {
            $amount = calculate_item_amount((float)$item['quantity'], (float)$item['unit_price']);
            $stmt->execute([
                'invoice_id'  => $invoiceId,
                'product_id'  => !empty($item['product_id']) ? $item['product_id'] : null,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit'        => $item['unit'] ?? 'pcs',
                'unit_price'  => $item['unit_price'],
                'amount'      => $amount,
                'sort_order'  => $i,
            ]);
        }
    }

    public function findByInvoice(int $invoiceId): array {
        $stmt = $this->db->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY sort_order ASC");
        $stmt->execute(['id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    public function deleteByInvoice(int $invoiceId): void {
        $stmt = $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = :id");
        $stmt->execute(['id' => $invoiceId]);
    }
}
