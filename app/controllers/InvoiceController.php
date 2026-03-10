<?php
class InvoiceController extends Controller {
    private InvoiceService $invoiceService;

    public function __construct() {
        parent::__construct();
        $this->invoiceService = new InvoiceService();
    }

    public function index(): void {
        $invoiceModel = new Invoice();
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $invoices = $invoiceModel->findAllWithClientFiltered($search ?: null, $status ?: null);
        $this->view('invoices/index', [
            'invoices' => $invoices,
            'search' => $search,
            'statusFilter' => $status,
        ]);
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
            $this->setFlash('danger', 'Client and at least one invoice item are required.');
            $this->redirect('invoices/create');
            return;
        }

        try {
            $invoiceId = $this->invoiceService->createInvoice($data, $items);
            $this->setFlash('success', 'Invoice created successfully.');
            $this->redirect('invoices/show/' . $invoiceId);
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Failed to create invoice: ' . $e->getMessage());
            $this->redirect('invoices/create');
        }
    }

    public function show(string $id): void {
        $invoice = $this->invoiceService->getFullInvoice((int)$id);
        if (!$invoice) {
            $this->setFlash('danger', 'Invoice not found.');
            $this->redirect('invoices');
            return;
        }
        $currencies = (new Currency())->findActive();
        $attachments = (new AdvancedOpsService())->listAttachments('invoice', (int)$id);
        $this->view('invoices/show', ['invoice' => $invoice, 'currencies' => $currencies, 'attachments' => $attachments]);
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
            $this->setFlash('success', 'Invoice updated successfully.');
            $this->redirect('invoices/show/' . $id);
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Failed to update invoice: ' . $e->getMessage());
            $this->redirect('invoices/edit/' . $id);
        }
    }

    public function delete(string $id): void {
        $invoiceModel = new Invoice();
        $old = $invoiceModel->find((int)$id);
        if ($old && in_array($old['status'], ['draft', 'cancelled'])) {
            $invoiceModel->delete((int)$id);
            AuditLog::log('invoice', (int)$id, 'deleted', $old, null);
            $this->setFlash('success', 'Invoice deleted successfully.');
        } else {
            $this->setFlash('danger', 'Only draft/cancelled invoices can be deleted.');
        }
        $this->redirect('invoices');
    }

    public function pdf(string $id): void {
        $invoice = $this->invoiceService->getFullInvoice((int)$id);
        if (!$invoice) {
            $this->setFlash('danger', 'Invoice not found.');
            $this->redirect('invoices');
            return;
        }
        $pdfService = new PdfService();
        $pdfService->outputPdf($invoice);
    }

    public function sendEmail(string $id): void {
        $invoice = $this->invoiceService->getFullInvoice((int)$id);
        if (!$invoice) {
            $this->setFlash('danger', 'Invoice not found.');
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
            $this->setFlash('success', 'Invoice email sent to ' . $invoice['client_email']);
        } else {
            $this->setFlash('danger', 'Failed to send email. Please check SMTP configuration.');
        }
        $this->redirect('invoices/show/' . $id);
    }
}
