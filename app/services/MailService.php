<?php
/**
 * MailService - Send invoice via email (SMTP)
 */
class MailService {
    /**
     * Send invoice email with HTML body
     */
    public function sendInvoice(array $invoice): bool {
        $to      = $invoice['client_email'];
        $subject = 'Invoice ' . $invoice['invoice_number'] . ' - ' . COMPANY_NAME;

        $pdfService = new PdfService();
        $htmlBody = $pdfService->generateInvoiceHtml($invoice);

        // Wrap with email template
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
                <p>Dear " . e($invoice['client_name']) . ",</p>
                <p>Please find your invoice <strong>" . e($invoice['invoice_number']) . "</strong>
                    with total amount <strong>" . format_currency((float)$invoice['total'], $invoice['currency']) . "</strong>,
                    due on <strong>" . format_date($invoice['due_date']) . "</strong>.</p>
                <p>Please complete payment before the due date.</p>
                <p>To pay online, use the following link:<br>
                    <a href='" . APP_URL . "/invoices/show/" . $invoice['id'] . "'>View & Pay Invoice</a></p>
            <hr>
            {$htmlBody}
            <hr>
                <p style='font-size:12px;color:#999'>This email was sent automatically by " . e(APP_NAME) . "</p>
        </div>";

        // Use PHP mail() or SMTP
        if (MAIL_HOST && MAIL_USERNAME) {
            return $this->sendSmtp($to, $subject, $body);
        }

        // Fallback to PHP mail()
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";

        return mail($to, $subject, $body, $headers);
    }

    public function sendRawHtml(string $to, string $subject, string $body): bool {
        if (MAIL_HOST && MAIL_USERNAME) {
            return $this->sendSmtp($to, $subject, $body);
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
        return mail($to, $subject, $body, $headers);
    }

    /**
     * Send via SMTP (basic socket)
     */
    private function sendSmtp(string $to, string $subject, string $body): bool {
        $host = MAIL_HOST;
        $port = (int) MAIL_PORT;
        $user = MAIL_USERNAME;
        $pass = MAIL_PASSWORD;
        $from = MAIL_FROM;

        $boundary = md5(uniqid());

        $message  = "From: " . MAIL_FROM_NAME . " <{$from}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $body;

        $errno = 0;
        $errstr = '';
        $sock = @fsockopen($port === 465 ? "ssl://{$host}" : $host, $port, $errno, $errstr, 30);
        if (!$sock) return false;

        $this->smtpRead($sock);
        $this->smtpCmd($sock, "EHLO " . gethostname());

        if ($port === 587) {
            $this->smtpCmd($sock, "STARTTLS");
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->smtpCmd($sock, "EHLO " . gethostname());
        }

        $this->smtpCmd($sock, "AUTH LOGIN");
        $this->smtpCmd($sock, base64_encode($user));
        $this->smtpCmd($sock, base64_encode($pass));
        $this->smtpCmd($sock, "MAIL FROM:<{$from}>");
        $this->smtpCmd($sock, "RCPT TO:<{$to}>");
        $this->smtpCmd($sock, "DATA");
        fwrite($sock, $message . "\r\n.\r\n");
        $this->smtpRead($sock);
        $this->smtpCmd($sock, "QUIT");
        fclose($sock);

        return true;
    }

    private function smtpCmd($sock, string $cmd): string {
        fwrite($sock, $cmd . "\r\n");
        return $this->smtpRead($sock);
    }

    private function smtpRead($sock): string {
        $response = '';
        while ($line = fgets($sock, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    }
}
