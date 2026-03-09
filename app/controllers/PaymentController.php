<?php
class PaymentController extends Controller {
    private PaymentService $paymentService;

    public function __construct() {
        parent::__construct();
        $this->paymentService = new PaymentService();
    }

    /**
     * POST /payments/checkout - create checkout session
     */
    public function checkout(): void {
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        if (!$invoiceId) {
            $this->json(['error' => 'Invoice ID required'], 400);
            return;
        }

        try {
            $result = $this->paymentService->createCheckout($invoiceId);

            // If Stripe, redirect to checkout URL
            if (!empty($result['checkout_url'])) {
                header('Location: ' . $result['checkout_url']);
                exit;
            }

            // If Midtrans, redirect to payment page
            if (!empty($result['redirect_url'])) {
                header('Location: ' . $result['redirect_url']);
                exit;
            }

            $this->json($result);
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Checkout gagal: ' . $e->getMessage());
            $this->redirect('invoices/show/' . $invoiceId);
        }
    }

    /**
     * POST /payments/webhook - handle payment gateway webhook
     */
    public function webhook(): void {
        // Determine provider
        $provider = PAYMENT_PROVIDER;

        // Check if it's a Stripe webhook (has stripe signature header)
        $stripeSignature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        if ($stripeSignature) {
            $provider = 'stripe';
        }

        try {
            if ($provider === 'stripe') {
                $payload = file_get_contents('php://input');
                $result = $this->paymentService->handleStripeWebhook($payload, $stripeSignature);
            } else {
                $payload = file_get_contents('php://input');
                $notification = json_decode($payload, true);
                if (!$notification) {
                    $this->json(['error' => 'Invalid payload'], 400);
                    return;
                }
                $result = $this->paymentService->handleMidtransWebhook($notification);
            }

            $this->json($result);
        } catch (\Exception $e) {
            http_response_code(400);
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /payments/{id}/status
     */
    public function status(string $id): void {
        $payment = $this->paymentService->getPaymentStatus((int)$id);
        if (!$payment) {
            $this->json(['error' => 'Payment not found'], 404);
            return;
        }
        $this->json([
            'id'     => $payment['id'],
            'status' => $payment['status'],
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'provider' => $payment['provider'],
        ]);
    }

    /**
     * GET /payments/success - success redirect page
     */
    public function success(): void {
        $invoiceId = (int)($_GET['invoice_id'] ?? 0);
        $this->view('payments/success', ['invoiceId' => $invoiceId]);
    }

    /**
     * GET /payments/cancel - cancel redirect page
     */
    public function cancel(): void {
        $invoiceId = (int)($_GET['invoice_id'] ?? 0);
        $this->view('payments/cancel', ['invoiceId' => $invoiceId]);
    }

    /**
     * GET /payments/pending - pending page
     */
    public function pending(): void {
        $invoiceId = (int)($_GET['invoice_id'] ?? 0);
        $this->view('payments/pending', ['invoiceId' => $invoiceId]);
    }

    /**
     * POST /payments/record/{id} - record manual payment
     */
    public function recordManual(string $id): void {
        $data = [
            'amount'         => (float)($_POST['amount'] ?? 0),
            'currency'       => $_POST['currency'] ?? BASE_CURRENCY,
            'payment_method' => $_POST['payment_method'] ?? 'bank_transfer',
            'payment_date'   => $_POST['payment_date'] ?? date('Y-m-d H:i:s'),
            'notes'          => trim($_POST['notes'] ?? ''),
        ];

        if ($data['amount'] <= 0) {
            $this->setFlash('danger', 'Jumlah pembayaran harus lebih dari 0.');
            $this->redirect('invoices/show/' . $id);
            return;
        }

        try {
            $this->paymentService->recordManualPayment((int)$id, $data);
            $this->setFlash('success', 'Pembayaran berhasil dicatat.');
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
        $this->redirect('invoices/show/' . $id);
    }
}
