<?php
/**
 * PaymentService - Stripe & Midtrans integration, webhook verification, idempotency
 */
class PaymentService {
    private Payment $paymentModel;
    private Invoice $invoiceModel;
    private CurrencyService $currencyService;

    public function __construct() {
        $this->paymentModel    = new Payment();
        $this->invoiceModel    = new Invoice();
        $this->currencyService = new CurrencyService();
    }

    /**
     * Create checkout session based on provider
     */
    public function createCheckout(int $invoiceId): array {
        $invoice = $this->invoiceModel->findWithClient($invoiceId);
        if (!$invoice) throw new \RuntimeException('Invoice not found');

        $idempotencyKey = generate_idempotency_key();

        $provider = PAYMENT_PROVIDER;
        if ($provider === 'stripe') {
            return $this->createStripeCheckout($invoice, $idempotencyKey);
        } elseif ($provider === 'midtrans') {
            return $this->createMidtransCheckout($invoice, $idempotencyKey);
        }

        throw new \RuntimeException("Unknown payment provider: $provider");
    }

    /**
     * Create Stripe Checkout Session
     */
    private function createStripeCheckout(array $invoice, string $idempotencyKey): array {
        $currency = strtolower($invoice['currency']);
        // Stripe expects amounts in smallest unit (cents/sen)
        $multiplier = in_array($currency, ['idr', 'jpy']) ? 1 : 100;
        $amount = (int) round($invoice['total'] * $multiplier);

        $postData = http_build_query([
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][product_data][name]' => 'Invoice ' . $invoice['invoice_number'],
            'line_items[0][price_data][unit_amount]' => $amount,
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'success_url' => APP_URL . '/payments/success?invoice_id=' . $invoice['id'],
            'cancel_url'  => APP_URL . '/payments/cancel?invoice_id=' . $invoice['id'],
            'metadata[invoice_id]' => $invoice['id'],
            'metadata[invoice_number]' => $invoice['invoice_number'],
            'metadata[idempotency_key]' => $idempotencyKey,
            'client_reference_id' => $invoice['invoice_number'],
        ]);

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . STRIPE_SECRET,
                'Idempotency-Key: ' . $idempotencyKey,
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($httpCode !== 200 || empty($data['id'])) {
            throw new \RuntimeException('Stripe checkout failed: ' . ($data['error']['message'] ?? 'Unknown error'));
        }

        // Store pending payment
        $this->paymentModel->create([
            'invoice_id'          => $invoice['id'],
            'currency'            => $invoice['currency'],
            'amount'              => $invoice['total'],
            'amount_in_base'      => $invoice['total_in_base'],
            'exchange_rate'       => $invoice['exchange_rate'],
            'provider'            => 'stripe',
            'provider_payment_id' => $data['id'],
            'idempotency_key'     => $idempotencyKey,
            'status'              => 'pending',
        ]);

        return [
            'provider'     => 'stripe',
            'checkout_url' => $data['url'],
            'session_id'   => $data['id'],
        ];
    }

    /**
     * Create Midtrans Snap transaction
     */
    private function createMidtransCheckout(array $invoice, string $idempotencyKey): array {
        $baseUrl = MIDTRANS_IS_PRODUCTION
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $orderId = $invoice['invoice_number'] . '-' . substr($idempotencyKey, 0, 8);

        $payload = json_encode([
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) round($invoice['total']),
            ],
            'customer_details' => [
                'first_name' => $invoice['client_name'],
                'email'      => $invoice['client_email'],
                'phone'      => $invoice['client_phone'] ?? '',
            ],
            'callbacks' => [
                'finish' => APP_URL . '/payments/success?invoice_id=' . $invoice['id'],
            ],
        ]);

        $ch = curl_init($baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':'),
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($httpCode !== 201 || empty($data['token'])) {
            throw new \RuntimeException('Midtrans checkout failed: ' . ($data['error_messages'][0] ?? json_encode($data)));
        }

        // Store pending payment
        $this->paymentModel->create([
            'invoice_id'          => $invoice['id'],
            'currency'            => $invoice['currency'],
            'amount'              => $invoice['total'],
            'amount_in_base'      => $invoice['total_in_base'],
            'exchange_rate'       => $invoice['exchange_rate'],
            'provider'            => 'midtrans',
            'provider_payment_id' => $orderId,
            'idempotency_key'     => $idempotencyKey,
            'status'              => 'pending',
        ]);

        return [
            'provider'      => 'midtrans',
            'snap_token'    => $data['token'],
            'redirect_url'  => $data['redirect_url'],
        ];
    }

    /**
     * Handle Stripe webhook
     */
    public function handleStripeWebhook(string $payload, string $sigHeader): array {
        // Verify signature
        $this->verifyStripeSignature($payload, $sigHeader);

        $event = json_decode($payload, true);
        if (!$event || !isset($event['type'])) {
            throw new \RuntimeException('Invalid webhook payload');
        }

        if ($event['type'] !== 'checkout.session.completed') {
            return ['status' => 'ignored', 'type' => $event['type']];
        }

        $session = $event['data']['object'];
        $sessionId = $session['id'];

        // Idempotency: check if already processed
        $existing = $this->paymentModel->findByProviderPaymentId($sessionId);
        if ($existing && $existing['status'] === 'success') {
            return ['status' => 'duplicate', 'payment_id' => $existing['id']];
        }

        $invoiceId = (int) ($session['metadata']['invoice_id'] ?? 0);
        if (!$invoiceId) {
            throw new \RuntimeException('Missing invoice_id in metadata');
        }

        $invoice = $this->invoiceModel->find($invoiceId);
        if (!$invoice) throw new \RuntimeException('Invoice not found');

        if ($existing) {
            // Update existing pending payment
            $this->paymentModel->updateStatus($existing['id'], 'success');
            $paymentId = $existing['id'];
        } else {
            $exchangeRate = $invoice['exchange_rate'] ?? 1;
            $amountInBase = $invoice['total_in_base'] ?? $invoice['total'];

            $paymentId = $this->paymentModel->create([
                'invoice_id'          => $invoiceId,
                'currency'            => $invoice['currency'],
                'amount'              => ($session['amount_total'] ?? $invoice['total'] * 100) / (in_array(strtolower($invoice['currency']), ['idr','jpy']) ? 1 : 100),
                'amount_in_base'      => $amountInBase,
                'exchange_rate'       => $exchangeRate,
                'provider'            => 'stripe',
                'provider_payment_id' => $sessionId,
                'idempotency_key'     => $session['metadata']['idempotency_key'] ?? generate_idempotency_key(),
                'status'              => 'success',
                'payment_method'      => $session['payment_method_types'][0] ?? 'card',
                'raw_response'        => $session,
            ]);
        }

        // Update invoice payment status
        $this->invoiceModel->updateAmountPaid($invoiceId);

        AuditLog::log('payment', $paymentId, 'webhook_received', null, [
            'provider' => 'stripe',
            'event'    => $event['type'],
            'session'  => $sessionId,
        ]);

        return ['status' => 'success', 'payment_id' => $paymentId];
    }

    /**
     * Handle Midtrans webhook notification
     */
    public function handleMidtransWebhook(array $notification): array {
        // Verify signature
        $this->verifyMidtransSignature($notification);

        $orderId = $notification['order_id'] ?? '';
        $transactionStatus = $notification['transaction_status'] ?? '';
        $fraudStatus = $notification['fraud_status'] ?? 'accept';

        // Idempotency: check if already processed
        $existing = $this->paymentModel->findByProviderPaymentId($orderId);
        if ($existing && $existing['status'] === 'success') {
            return ['status' => 'duplicate', 'payment_id' => $existing['id']];
        }

        // Determine status
        $paymentStatus = 'pending';
        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            if ($fraudStatus === 'accept') $paymentStatus = 'success';
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $paymentStatus = 'failed';
        }

        if ($existing) {
            $this->paymentModel->updateStatus($existing['id'], $paymentStatus);
            $paymentId = $existing['id'];
            $invoiceId = $existing['invoice_id'];
        } else {
            // Extract invoice_number from order_id
            $parts = explode('-', $orderId);
            array_pop($parts); // Remove random suffix
            $invoiceNumber = implode('-', $parts);

            // Find invoice
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM invoices WHERE invoice_number = :num LIMIT 1");
            $stmt->execute(['num' => $invoiceNumber]);
            $invoice = $stmt->fetch();

            if (!$invoice) throw new \RuntimeException("Invoice not found for order: $orderId");

            $invoiceId = $invoice['id'];
            $exchangeRate = $invoice['exchange_rate'] ?? 1;
            $amount = (float)($notification['gross_amount'] ?? $invoice['total']);
            $amountInBase = $invoice['total_in_base'] ?? $amount;

            $paymentId = $this->paymentModel->create([
                'invoice_id'          => $invoiceId,
                'currency'            => $invoice['currency'],
                'amount'              => $amount,
                'amount_in_base'      => $amountInBase,
                'exchange_rate'       => $exchangeRate,
                'provider'            => 'midtrans',
                'provider_payment_id' => $orderId,
                'idempotency_key'     => $notification['transaction_id'] ?? generate_idempotency_key(),
                'status'              => $paymentStatus,
                'payment_method'      => $notification['payment_type'] ?? null,
                'raw_response'        => $notification,
            ]);
        }

        if ($paymentStatus === 'success') {
            $this->invoiceModel->updateAmountPaid($invoiceId);
        }

        AuditLog::log('payment', $paymentId, 'webhook_received', null, [
            'provider'    => 'midtrans',
            'status'      => $transactionStatus,
            'order_id'    => $orderId,
        ]);

        return ['status' => $paymentStatus, 'payment_id' => $paymentId];
    }

    /**
     * Verify Stripe webhook signature
     */
    private function verifyStripeSignature(string $payload, string $sigHeader): void {
        if (!STRIPE_WEBHOOK_SECRET) return; // Skip in dev if not set

        $parts = [];
        foreach (explode(',', $sigHeader) as $item) {
            [$k, $v] = explode('=', trim($item), 2);
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';

        if (!$timestamp || !$signature) {
            throw new \RuntimeException('Invalid Stripe signature header');
        }

        // Tolerance: 5 minutes
        if (abs(time() - (int)$timestamp) > 300) {
            throw new \RuntimeException('Stripe webhook timestamp too old');
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, STRIPE_WEBHOOK_SECRET);

        if (!hash_equals($expected, $signature)) {
            throw new \RuntimeException('Invalid Stripe webhook signature');
        }
    }

    /**
     * Verify Midtrans notification signature
     */
    private function verifyMidtransSignature(array $notification): void {
        if (!MIDTRANS_SERVER_KEY) return;

        $orderId     = $notification['order_id'] ?? '';
        $statusCode  = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $serverKey   = MIDTRANS_SERVER_KEY;

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $received = $notification['signature_key'] ?? '';

        if (!hash_equals($expected, $received)) {
            throw new \RuntimeException('Invalid Midtrans signature');
        }
    }

    /**
     * Record manual payment
     */
    public function recordManualPayment(int $invoiceId, array $data): int {
        $invoice = $this->invoiceModel->find($invoiceId);
        if (!$invoice) throw new \RuntimeException('Invoice not found');

        $paymentCurrency = $data['currency'] ?? $invoice['currency'];
        $amount = (float) $data['amount'];
        $exchangeRate = null;
        $amountInBase = $amount;

        if ($paymentCurrency !== BASE_CURRENCY) {
            try {
                $exchangeRate = $this->currencyService->getRate($paymentCurrency, BASE_CURRENCY);
                $amountInBase = convert_currency($amount, $exchangeRate);
            } catch (\Exception $e) {
                // Use 1:1 if rate unavailable
            }
        }

        $paymentId = $this->paymentModel->create([
            'invoice_id'      => $invoiceId,
            'currency'        => $paymentCurrency,
            'amount'          => $amount,
            'amount_in_base'  => $amountInBase,
            'exchange_rate'   => $exchangeRate,
            'provider'        => 'manual',
            'idempotency_key' => generate_idempotency_key(),
            'status'          => 'success',
            'payment_method'  => $data['payment_method'] ?? 'bank_transfer',
            'payment_date'    => $data['payment_date'] ?? date('Y-m-d H:i:s'),
            'notes'           => $data['notes'] ?? null,
        ]);

        $this->invoiceModel->updateAmountPaid($invoiceId);

        AuditLog::log('payment', $paymentId, 'manual_payment', null, [
            'invoice_id' => $invoiceId,
            'amount'     => $amount,
            'currency'   => $paymentCurrency,
        ]);

        return $paymentId;
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(int $paymentId): ?array {
        return $this->paymentModel->find($paymentId);
    }
}
