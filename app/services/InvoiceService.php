<?php
/**
 * InvoiceService - business logic for invoice creation with multi-currency
 */
class InvoiceService {
    private Invoice $invoiceModel;
    private InvoiceItem $itemModel;
    private CurrencyService $currencyService;

    public function __construct() {
        $this->invoiceModel    = new Invoice();
        $this->itemModel       = new InvoiceItem();
        $this->currencyService = new CurrencyService();
    }

    /**
     * Create a complete invoice with items
     */
    public function createInvoice(array $data, array $items): int {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Generate atomic invoice number
            $invoiceNumber = $this->invoiceModel->generateNumber();

            // Calculate totals
            $subtotal = 0;
            foreach ($items as &$item) {
                $item['amount'] = calculate_item_amount((float)$item['quantity'], (float)$item['unit_price']);
                $subtotal += $item['amount'];
            }
            unset($item);

            $totals = calculate_invoice_total($subtotal, (float)($data['tax_rate'] ?? 0), (float)($data['discount_amount'] ?? 0));

            // Get exchange rate if not base currency
            $currency = $data['currency'] ?? BASE_CURRENCY;
            $exchangeRate = null;
            $totalInBase = $totals['total'];

            if ($currency !== BASE_CURRENCY) {
                try {
                    $exchangeRate = $this->currencyService->getRate($currency, BASE_CURRENCY);
                    $totalInBase = convert_currency($totals['total'], $exchangeRate);
                } catch (\Exception $e) {
                    // If rate not available, leave null
                    $totalInBase = null;
                }
            }

            // Create invoice
            $invoiceId = $this->invoiceModel->create([
                'invoice_number'  => $invoiceNumber,
                'client_id'       => $data['client_id'],
                'currency'        => $currency,
                'exchange_rate'   => $exchangeRate,
                'subtotal'        => $totals['subtotal'],
                'tax_rate'        => $totals['tax_rate'],
                'tax_amount'      => $totals['tax_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total'           => $totals['total'],
                'total_in_base'   => $totalInBase,
                'status'          => $data['status'] ?? 'draft',
                'issue_date'      => $data['issue_date'],
                'due_date'        => $data['due_date'],
                'notes'           => $data['notes'] ?? null,
            ]);

            // Create items
            $this->itemModel->createBatch($invoiceId, $items);

            // Audit log
            AuditLog::log('invoice', $invoiceId, 'created', null, [
                'invoice_number' => $invoiceNumber,
                'total'          => $totals['total'],
                'currency'       => $currency,
            ]);

            $db->commit();
            return $invoiceId;

        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Update invoice with items
     */
    public function updateInvoice(int $id, array $data, array $items): bool {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $oldInvoice = $this->invoiceModel->find($id);

            // Recalculate
            $subtotal = 0;
            foreach ($items as &$item) {
                $item['amount'] = calculate_item_amount((float)$item['quantity'], (float)$item['unit_price']);
                $subtotal += $item['amount'];
            }
            unset($item);

            $totals = calculate_invoice_total($subtotal, (float)($data['tax_rate'] ?? 0), (float)($data['discount_amount'] ?? 0));

            $currency = $data['currency'] ?? $oldInvoice['currency'];
            $exchangeRate = $oldInvoice['exchange_rate'];
            $totalInBase = $totals['total'];

            if ($currency !== BASE_CURRENCY) {
                try {
                    $exchangeRate = $this->currencyService->getRate($currency, BASE_CURRENCY);
                    $totalInBase = convert_currency($totals['total'], $exchangeRate);
                } catch (\Exception $e) {
                    $totalInBase = null;
                }
            }

            $this->invoiceModel->update($id, [
                'client_id'       => $data['client_id'],
                'currency'        => $currency,
                'exchange_rate'   => $exchangeRate,
                'subtotal'        => $totals['subtotal'],
                'tax_rate'        => $totals['tax_rate'],
                'tax_amount'      => $totals['tax_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total'           => $totals['total'],
                'total_in_base'   => $totalInBase,
                'issue_date'      => $data['issue_date'],
                'due_date'        => $data['due_date'],
                'notes'           => $data['notes'] ?? null,
            ]);

            // Replace items
            $this->itemModel->deleteByInvoice($id);
            $this->itemModel->createBatch($id, $items);

            AuditLog::log('invoice', $id, 'updated', $oldInvoice, $data);

            $db->commit();
            return true;

        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Get full invoice data with items and payments
     */
    public function getFullInvoice(int $id): ?array {
        $invoice = $this->invoiceModel->findWithClient($id);
        if (!$invoice) return null;

        $invoice['items']    = $this->itemModel->findByInvoice($id);
        $invoice['payments'] = (new Payment())->findByInvoice($id);

        return $invoice;
    }
}
