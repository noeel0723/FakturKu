<?php
class OperationsController extends Controller {
    private AdvancedOpsService $ops;

    public function __construct() {
        parent::__construct();
        $this->ops = new AdvancedOpsService();
    }

    public function agingReport(): void {
        $this->requireRoles(['owner', 'finance', 'staff']);
        $asOfDate = $_GET['as_of'] ?? date('Y-m-d');
        $report = $this->ops->getAgingReport($asOfDate);
        $this->view('ops/aging_report', [
            'asOfDate' => $asOfDate,
            'buckets' => $report['buckets'],
            'rows' => $report['rows'],
        ]);
    }

    public function reminders(): void {
        $this->requireRoles(['owner', 'finance']);
        $logs = $this->ops->listReminderLogs();
        $this->view('ops/reminders', ['logs' => $logs]);
    }

    public function runReminders(): void {
        $this->requireRoles(['owner', 'finance']);
        $result = $this->ops->runAutomatedReminders(date('Y-m-d'));
        $this->setFlash('success', "Reminder job completed: sent {$result['sent']}, skipped {$result['skipped']}, failed {$result['failed']}.");
        $this->redirect('ops/reminders');
    }

    public function creditNotes(): void {
        $this->requireRoles(['owner', 'finance']);
        $notes = $this->ops->listCreditNotes();
        $invoices = (new Invoice())->findAllWithClient();
        $this->view('ops/credit_notes', ['creditNotes' => $notes, 'invoices' => $invoices]);
    }

    public function storeCreditNote(): void {
        $this->requireRoles(['owner', 'finance']);
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $invoice = (new Invoice())->find($invoiceId);
        if (!$invoice) {
            $this->setFlash('danger', 'Invoice not found for credit note.');
            $this->redirect('ops/credit-notes');
            return;
        }

        $id = $this->ops->createCreditNote([
            'invoice_id' => $invoiceId,
            'client_id' => $invoice['client_id'],
            'currency' => $invoice['currency'],
            'amount' => (float)($_POST['amount'] ?? 0),
            'reason' => trim($_POST['reason'] ?? ''),
            'issued_at' => $_POST['issued_at'] ?? date('Y-m-d'),
        ]);

        $this->setFlash('success', "Credit note #{$id} created.");
        $this->redirect('ops/credit-notes');
    }

    public function applyCreditNote(string $id): void {
        $this->requireRoles(['owner', 'finance']);
        $this->ops->applyCreditNote((int)$id);
        $this->setFlash('success', 'Credit note applied to invoice total successfully.');
        $this->redirect('ops/credit-notes');
    }

    public function recurring(): void {
        $this->requireRoles(['owner', 'finance']);
        $templates = $this->ops->listRecurringTemplates();
        $clients = (new Client())->findAll('name ASC');
        $products = (new Product())->findAll('name ASC');
        $currencies = (new Currency())->findActive();
        $this->view('ops/recurring', compact('templates', 'clients', 'products', 'currencies'));
    }

    public function storeRecurring(): void {
        $this->requireRoles(['owner', 'finance']);
        $items = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (empty($item['description'])) continue;
                $items[] = [
                    'description' => trim($item['description']),
                    'quantity' => (float)($item['quantity'] ?? 1),
                    'unit' => trim($item['unit'] ?? 'pcs'),
                    'unit_price' => (float)($item['unit_price'] ?? 0),
                ];
            }
        }

        if (empty($items)) {
            $this->setFlash('danger', 'Recurring template requires at least one item.');
            $this->redirect('ops/recurring');
            return;
        }

        $this->ops->createRecurringTemplate([
            'template_name' => trim($_POST['template_name'] ?? 'Recurring Template'),
            'client_id' => (int)($_POST['client_id'] ?? 0),
            'currency' => $_POST['currency'] ?? BASE_CURRENCY,
            'frequency' => $_POST['frequency'] ?? 'monthly',
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'next_issue_date' => $_POST['next_issue_date'] ?? date('Y-m-d'),
            'tax_rate' => (float)($_POST['tax_rate'] ?? 0),
            'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'items' => $items,
        ]);

        $this->setFlash('success', 'Recurring template created.');
        $this->redirect('ops/recurring');
    }

    public function runRecurring(): void {
        $this->requireRoles(['owner', 'finance']);
        $result = $this->ops->runRecurringGeneration(date('Y-m-d'));
        $this->setFlash('success', "Recurring job finished: {$result['generated']} invoices generated from {$result['templates_processed']} templates.");
        $this->redirect('ops/recurring');
    }

    public function quotes(): void {
        $this->requireRoles(['owner', 'finance', 'staff']);
        $quotes = $this->ops->listQuotes();
        $clients = (new Client())->findAll('name ASC');
        $currencies = (new Currency())->findActive();
        $this->view('ops/quotes', compact('quotes', 'clients', 'currencies'));
    }

    public function storeQuote(): void {
        $this->requireRoles(['owner', 'finance', 'staff']);
        $items = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (empty($item['description'])) continue;
                $items[] = [
                    'description' => trim($item['description']),
                    'quantity' => (float)($item['quantity'] ?? 1),
                    'unit' => trim($item['unit'] ?? 'pcs'),
                    'unit_price' => (float)($item['unit_price'] ?? 0),
                ];
            }
        }

        if (empty($items)) {
            $this->setFlash('danger', 'Quote requires at least one line item.');
            $this->redirect('ops/quotes');
            return;
        }

        $this->ops->createQuote([
            'client_id' => (int)($_POST['client_id'] ?? 0),
            'currency' => $_POST['currency'] ?? BASE_CURRENCY,
            'tax_rate' => (float)($_POST['tax_rate'] ?? 0),
            'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
            'valid_until' => $_POST['valid_until'] ?? null,
            'notes' => trim($_POST['notes'] ?? ''),
        ], $items);

        $this->setFlash('success', 'Quote created successfully.');
        $this->redirect('ops/quotes');
    }

    public function convertQuote(string $id): void {
        $this->requireRoles(['owner', 'finance', 'staff']);
        $invoiceId = $this->ops->convertQuoteToInvoice((int)$id);
        if (!$invoiceId) {
            $this->setFlash('danger', 'Quote conversion failed or quote already converted.');
            $this->redirect('ops/quotes');
            return;
        }

        $this->setFlash('success', 'Quote converted to invoice successfully.');
        $this->redirect('invoices/show/' . $invoiceId);
    }

    public function taxProfiles(): void {
        $this->requireRoles(['owner', 'finance']);
        $profiles = $this->ops->listTaxProfiles();
        $preview = null;
        if (!empty($_GET['preview_amount']) && !empty($_GET['profile_ids'])) {
            $profileIds = array_map('intval', explode(',', $_GET['profile_ids']));
            $preview = $this->ops->calculateTaxPreview((float)$_GET['preview_amount'], $profileIds);
        }
        $this->view('ops/tax_profiles', ['profiles' => $profiles, 'preview' => $preview]);
    }

    public function storeTaxProfile(): void {
        $this->requireRoles(['owner', 'finance']);
        $this->ops->createTaxProfile([
            'name' => trim($_POST['name'] ?? ''),
            'tax_type' => $_POST['tax_type'] ?? 'vat',
            'calculation_method' => $_POST['calculation_method'] ?? 'percentage',
            'rate' => (float)($_POST['rate'] ?? 0),
            'fixed_amount' => (float)($_POST['fixed_amount'] ?? 0),
            'is_compound' => (int)($_POST['is_compound'] ?? 0),
            'is_active' => (int)($_POST['is_active'] ?? 1),
        ]);
        $this->setFlash('success', 'Tax profile created successfully.');
        $this->redirect('ops/tax-profiles');
    }

    public function reconciliation(): void {
        $this->requireRoles(['owner', 'finance']);
        $snap = $this->ops->getReconciliationSnapshot();
        $this->view('ops/reconciliation', ['summary' => $snap['summary'], 'mismatches' => $snap['mismatches']]);
    }

    public function attachmentsUpload(): void {
        $this->requireRoles(['owner', 'finance', 'staff']);
        $entityType = $_POST['entity_type'] ?? 'invoice';
        $entityId = (int)($_POST['entity_id'] ?? 0);
        if ($entityId <= 0 || empty($_FILES['attachment'])) {
            $this->setFlash('danger', 'Invalid attachment payload.');
            $this->redirect('dashboard');
            return;
        }

        $saved = $this->ops->saveAttachment($_FILES['attachment'], $entityType, $entityId, $this->currentUserRole());
        if ($saved) {
            $this->setFlash('success', 'Attachment uploaded successfully.');
        } else {
            $this->setFlash('danger', 'Attachment upload failed. Ensure file is PDF/JPG/PNG/WEBP.');
        }

        if ($entityType === 'invoice') {
            $this->redirect('invoices/show/' . $entityId);
            return;
        }
        $this->redirect('dashboard');
    }

    public function exports(): void {
        $this->requireRoles(['owner', 'finance']);
        $this->view('ops/exports', []);
    }

    public function exportData(): void {
        $this->requireRoles(['owner', 'finance']);
        $type = $_GET['type'] ?? 'invoices';
        $format = $_GET['format'] ?? 'csv';
        $rows = $this->ops->getExportRows($type);

        $this->ops->logExport($type, $format === 'xlsx' ? 'xlsx' : 'csv', $_GET, $this->currentUserRole());

        if ($format === 'xlsx') {
            // Lightweight XLSX-compatible XML export for broad spreadsheet support.
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $type . '_' . date('Ymd_His') . '.xls"');
            echo $this->toSpreadsheetXml($rows, ucfirst($type));
            exit;
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        }
        fclose($out);
        exit;
    }

    public function exportApi(): void {
        $this->requireRoles(['owner', 'finance']);
        $type = $_GET['type'] ?? 'invoices';
        $rows = $this->ops->getExportRows($type);
        $this->json([
            'type' => $type,
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    private function toSpreadsheetXml(array $rows, string $sheetName): string {
        $headers = !empty($rows) ? array_keys($rows[0]) : [];
        $xml = "<?xml version=\"1.0\"?>\n";
        $xml .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
        $xml .= "<Worksheet ss:Name=\"" . htmlspecialchars($sheetName) . "\"><Table>\n";

        if (!empty($headers)) {
            $xml .= "<Row>";
            foreach ($headers as $h) {
                $xml .= "<Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)$h) . "</Data></Cell>";
            }
            $xml .= "</Row>\n";
        }

        foreach ($rows as $row) {
            $xml .= "<Row>";
            foreach ($row as $value) {
                $xml .= "<Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)$value) . "</Data></Cell>";
            }
            $xml .= "</Row>\n";
        }

        $xml .= "</Table></Worksheet></Workbook>";
        return $xml;
    }
}
