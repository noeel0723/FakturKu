<?php
class InvoiceController extends Controller {
    private InvoiceService $invoiceService;

    public function __construct() {
        parent::__construct();
        $this->invoiceService = new InvoiceService();
    }

    public function index(): void {
        $invoiceModel = new Invoice();
        $invoices = $invoiceModel->findAllWithClient();
        $this->view('invoices/index', ['invoices' => $invoices]);
    }

    public function create(): void {
        $clients    = (new Client())->findAll('name ASC');
        $products   = (new Product())->findActive();
        $currencies = (new Currency())->findActive();
        $this->view('invoices/form', [
            'invoice'    => null,
            'items'      => [],
            'clients'    => $clients,
            'products'   => $products,
            'currencies' => $currencies,
        ]);
    }

    public function store(): void {
        $data = [
            'client_id'       => (int)($_POST['client_id'] ?? 0),
            'currency'        => $_POST['currency'] ?? BASE_CURRENCY,
            'tax_rate'        => (float)($_POST['tax_rate'] ?? 0),
            'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
            'issue_date'      => $_POST['issue_date'] ?? date('Y-m-d'),
            'due_date'        => $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'notes'           => trim($_POST['notes'] ?? ''),
            'status'          => $_POST['status'] ?? 'draft',
        ];

        $items = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (empty($item['description']) && empty($item['product_id'])) continue;
                $items[] = [
                    'product_id'  => !empty($item['product_id']) ? (int)$item['product_id'] : null,
                    'description' => trim($item['description'] ?? ''),
                    'quantity'    => (float)($item['quantity'] ?? 1),
                    'unit'        => trim($item['unit'] ?? 'pcs'),
                    'unit_price'  => (float)($item['unit_price'] ?? 0),
                ];
            }
        }

        if (!$data['client_id'] || empty($items)) {
            $this->setFlash('danger', 'Klien dan minimal 1 item wajib diisi.');
            $this->redirect('invoices/create');
            return;
        }

        try {
            $invoiceId = $this->invoiceService->createInvoice($data, $items);
            $this->setFlash('success', 'Invoice berhasil dibuat.');
            $this->redirect('invoices/show/' . $invoiceId);
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Gagal membuat invoice: ' . $e->getMessage());
            $this->redirect('invoices/create');
        }
    }

    public function show(string $id): void {
        $invoice = $this->invoiceService->getFullInvoice((int)$id);
        if (!$invoice) {
            $this->setFlash('danger', 'Invoice tidak ditemukan.');
            $this->redirect('invoices');
            return;
        }
        $currencies = (new Currency())->findActive();
        $this->view('invoices/show', ['invoice' => $invoice, 'currencies' => $currencies]);
    }

    public function edit(string $id): void {
        $invoice = $this->invoiceService->getFullInvoice((int)$id);
        if (!$invoice) {
            $this->redirect('invoices');
            return;
        }
        $clients    = (new Client())->findAll('name ASC');
        $products   = (new Product())->findActive();
        $currencies = (new Currency())->findActive();
        $this->view('invoices/form', [
            'invoice'    => $invoice,
            'items'      => $invoice['items'],
            'clients'    => $clients,
            'products'   => $products,
            'currencies' => $currencies,
        ]);
    }

    public function update(string $id): void {
        $data = [
            'client_id'       => (int)($_POST['client_id'] ?? 0),
            'currency'        => $_POST['currency'] ?? BASE_CURRENCY,
            'tax_rate'        => (float)($_POST['tax_rate'] ?? 0),
            'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
            'issue_date'      => $_POST['issue_date'] ?? date('Y-m-d'),
            'due_date'        => $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'notes'           => trim($_POST['notes'] ?? ''),
        ];

        $items = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (empty($item['description']) && empty($item['product_id'])) continue;
                $items[] = [
                    'product_id'  => !empty($item['product_id']) ? (int)$item['product_id'] : null,
                    'description' => trim($item['description'] ?? ''),
                    'quantity'    => (float)($item['quantity'] ?? 1),
                    'unit'        => trim($item['unit'] ?? 'pcs'),
                    'unit_price'  => (float)($item['unit_price'] ?? 0),
                ];
            }
        }

        try {
            $this->invoiceService->updateInvoice((int)$id, $data, $items);
            $this->setFlash('success', 'Invoice berhasil diperbarui.');
            $this->redirect('invoices/show/' . $id);
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Gagal update invoice: ' . $e->getMessage());
            $this->redirect('invoices/edit/' . $id);
        }
    }

    public function delete(string $id): void {
        $invoiceModel = new Invoice();
        $old = $invoiceModel->find((int)$id);
        if ($old && in_array($old['status'], ['draft', 'cancelled'])) {
            $invoiceModel->delete((int)$id);
            AuditLog::log('invoice', (int)$id, 'deleted', $old, null);
            $this->setFlash('success', 'Invoice berhasil dihapus.');
        } else {
            $this->setFlash('danger', 'Hanya invoice draft/cancelled yang bisa dihapus.');
        }
        $this->redirect('invoices');
    }

    public function pdf(string $id): void {
        $invoice = $this->invoiceService->getFullInvoice((int)$id);
        if (!$invoice) {
            $this->setFlash('danger', 'Invoice tidak ditemukan.');
            $this->redirect('invoices');
            return;
        }
        $pdfService = new PdfService();
        $pdfService->outputPdf($invoice);
    }

    public function sendEmail(string $id): void {
        $invoice = $this->invoiceService->getFullInvoice((int)$id);
        if (!$invoice) {
            $this->setFlash('danger', 'Invoice tidak ditemukan.');
            $this->redirect('invoices');
            return;
        }

        $mailService = new MailService();
        if ($mailService->sendInvoice($invoice)) {
            // Update status to sent if draft
            if ($invoice['status'] === 'draft') {
                (new Invoice())->update((int)$id, ['status' => 'sent']);
                AuditLog::log('invoice', (int)$id, 'status_changed', ['status' => 'draft'], ['status' => 'sent']);
            }
            $this->setFlash('success', 'Invoice berhasil dikirim ke ' . $invoice['client_email']);
        } else {
            $this->setFlash('danger', 'Gagal mengirim email. Periksa konfigurasi SMTP.');
        }
        $this->redirect('invoices/show/' . $id);
    }
}
