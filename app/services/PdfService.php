<?php
/**
 * PdfService - Generate invoice PDF using HTML/CSS (no external lib required)
 */
class PdfService {
    /**
     * Generate HTML invoice for PDF conversion
     */
    public function generateInvoiceHtml(array $invoice): string {
        $items = $invoice['items'] ?? [];
        $currency = $invoice['currency'];

        $itemsHtml = '';
        foreach ($items as $i => $item) {
            $no = $i + 1;
            $itemsHtml .= "<tr>
                <td style='text-align:center'>{$no}</td>
                <td>" . e($item['description']) . "</td>
                <td style='text-align:center'>{$item['quantity']} {$item['unit']}</td>
                <td style='text-align:right'>" . format_currency((float)$item['unit_price'], $currency) . "</td>
                <td style='text-align:right'>" . format_currency((float)$item['amount'], $currency) . "</td>
            </tr>";
        }

        $exchangeInfo = '';
        if ($currency !== BASE_CURRENCY && $invoice['exchange_rate']) {
            $exchangeInfo = "<p style='color:#666;font-size:12px;'>
                Exchange Rate: 1 {$currency} = " . number_format((float)$invoice['exchange_rate'], 4) . " " . BASE_CURRENCY . "<br>
                Total in " . BASE_CURRENCY . ": " . format_currency((float)$invoice['total_in_base'], BASE_CURRENCY) . "
            </p>";
        }

        $statusColor = match($invoice['status']) {
            'paid'           => '#28a745',
            'sent'           => '#007bff',
            'partially_paid' => '#ffc107',
            'overdue'        => '#dc3545',
            'cancelled'      => '#6c757d',
            default          => '#6c757d',
        };

        return "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #333; margin: 0; padding: 30px; }
    .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
    .company { font-size: 20px; font-weight: bold; color: #2563eb; }
    .invoice-title { font-size: 28px; color: #1e293b; text-align: right; }
    .invoice-number { font-size: 14px; color: #64748b; }
    .status { display: inline-block; padding: 4px 12px; border-radius: 20px; color: #fff; font-size: 12px; background: {$statusColor}; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background: #f1f5f9; padding: 10px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 13px; }
    td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .totals { margin-top: 20px; text-align: right; }
    .totals td { border: none; padding: 4px 10px; }
    .total-row { font-size: 18px; font-weight: bold; color: #2563eb; }
    .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
</style>
</head>
<body>
    <div class='header'>
        <div>
            <div class='company'>" . e(COMPANY_NAME) . "</div>
            <p style='font-size:12px;color:#64748b;margin:5px 0;'>" . e(COMPANY_ADDRESS) . "<br>" . e(COMPANY_PHONE) . "<br>" . e(COMPANY_EMAIL) . "</p>
        </div>
        <div style='text-align:right'>
            <div class='invoice-title'>INVOICE</div>
            <div class='invoice-number'>" . e($invoice['invoice_number']) . "</div>
            <div style='margin-top:8px'><span class='status'>" . strtoupper($invoice['status']) . "</span></div>
        </div>
    </div>

    <div style='display:flex;justify-content:space-between;margin-bottom:20px'>
        <div>
            <strong>Kepada:</strong><br>
            " . e($invoice['client_name']) . "<br>
            " . e($invoice['client_company'] ?? '') . "<br>
            " . e($invoice['client_address'] ?? '') . "<br>
            " . e($invoice['client_email']) . "
        </div>
        <div style='text-align:right'>
            <strong>Tanggal:</strong> " . format_date($invoice['issue_date']) . "<br>
            <strong>Jatuh Tempo:</strong> " . format_date($invoice['due_date']) . "<br>
            <strong>Mata Uang:</strong> {$currency}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style='width:40px;text-align:center'>No</th>
                <th>Deskripsi</th>
                <th style='width:80px;text-align:center'>Qty</th>
                <th style='width:120px;text-align:right'>Harga Satuan</th>
                <th style='width:120px;text-align:right'>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            {$itemsHtml}
        </tbody>
    </table>

    <table class='totals' style='width:300px;margin-left:auto'>
        <tr><td>Subtotal</td><td style='text-align:right'>" . format_currency((float)$invoice['subtotal'], $currency) . "</td></tr>
        <tr><td>Pajak ({$invoice['tax_rate']}%)</td><td style='text-align:right'>" . format_currency((float)$invoice['tax_amount'], $currency) . "</td></tr>
        <tr><td>Diskon</td><td style='text-align:right'>-" . format_currency((float)$invoice['discount_amount'], $currency) . "</td></tr>
        <tr class='total-row'><td>TOTAL</td><td style='text-align:right'>" . format_currency((float)$invoice['total'], $currency) . "</td></tr>
        <tr><td>Terbayar</td><td style='text-align:right'>" . format_currency((float)$invoice['amount_paid'], $currency) . "</td></tr>
        <tr style='font-weight:bold'><td>Sisa</td><td style='text-align:right'>" . format_currency(max(0, (float)$invoice['total'] - (float)$invoice['amount_paid']), $currency) . "</td></tr>
    </table>

    {$exchangeInfo}

    " . ($invoice['notes'] ? "<div style='margin-top:20px'><strong>Catatan:</strong><br>" . nl2br(e($invoice['notes'])) . "</div>" : '') . "

    <div class='footer'>
        Dokumen ini digenerate oleh " . e(APP_NAME) . " pada " . format_date(date('Y-m-d')) . "
    </div>
</body>
</html>";
    }

    /**
     * Output PDF using browser print or save HTML
     */
    public function outputPdf(array $invoice): void {
        $html = $this->generateInvoiceHtml($invoice);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        echo "<script>window.print();</script>";
    }

    /**
     * Save HTML as file (for email attachment)
     */
    public function saveAsHtml(array $invoice): string {
        $html = $this->generateInvoiceHtml($invoice);
        $filename = 'invoice_' . $invoice['invoice_number'] . '.html';
        $path = BASE_PATH . '/storage/invoices/' . $filename;

        if (!is_dir(BASE_PATH . '/storage/invoices')) {
            mkdir(BASE_PATH . '/storage/invoices', 0755, true);
        }

        file_put_contents($path, $html);
        return $path;
    }
}
