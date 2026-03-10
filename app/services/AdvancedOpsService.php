<?php
/**
 * Advanced operations service for reporting, reminders, quotes,
 * recurring billing, credit notes, reconciliation, attachments, and exports.
 */
class AdvancedOpsService {
    private PDO $db;
    private InvoiceService $invoiceService;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->invoiceService = new InvoiceService();
    }

    public function getAgingReport(string $asOfDate): array {
        $sql = "
            SELECT
                i.id,
                i.invoice_number,
                c.name AS client_name,
                i.currency,
                i.total,
                i.amount_paid,
                i.due_date,
                GREATEST(DATEDIFF(:as_of, i.due_date), 0) AS overdue_days,
                GREATEST(i.total - i.amount_paid, 0) AS outstanding
            FROM invoices i
            INNER JOIN clients c ON c.id = i.client_id
            WHERE i.status IN ('sent', 'partially_paid', 'overdue')
              AND (i.total - i.amount_paid) > 0
            ORDER BY overdue_days DESC, i.due_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['as_of' => $asOfDate]);
        $rows = $stmt->fetchAll();

        $buckets = [
            '0_30' => ['label' => '0-30 Days', 'count' => 0, 'amount' => 0],
            '31_60' => ['label' => '31-60 Days', 'count' => 0, 'amount' => 0],
            '61_90' => ['label' => '61-90 Days', 'count' => 0, 'amount' => 0],
            '90_plus' => ['label' => '90+ Days', 'count' => 0, 'amount' => 0],
        ];

        foreach ($rows as &$row) {
            $days = (int)$row['overdue_days'];
            if ($days <= 30) {
                $bucket = '0_30';
            } elseif ($days <= 60) {
                $bucket = '31_60';
            } elseif ($days <= 90) {
                $bucket = '61_90';
            } else {
                $bucket = '90_plus';
            }
            $row['bucket'] = $bucket;
            $buckets[$bucket]['count']++;
            $buckets[$bucket]['amount'] += (float)$row['outstanding'];
        }

        return ['rows' => $rows, 'buckets' => $buckets];
    }

    public function runAutomatedReminders(string $today): array {
        $sql = "
            SELECT i.*, c.name AS client_name, c.email AS client_email
            FROM invoices i
            INNER JOIN clients c ON c.id = i.client_id
            WHERE i.status IN ('sent','partially_paid','overdue')
              AND (i.total - i.amount_paid) > 0
        ";
        $invoices = $this->db->query($sql)->fetchAll();
        $mailService = new MailService();

        $results = ['sent' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($invoices as $invoice) {
            $due = new DateTime($invoice['due_date']);
            $now = new DateTime($today);
            $delta = (int)$now->diff($due)->format('%r%a');

            $reminderType = null;
            if ($delta === 7) $reminderType = 'before_7';
            if ($delta === 1) $reminderType = 'before_1';
            if ($delta === -3) $reminderType = 'after_3';
            if (!$reminderType) continue;

            $already = $this->db->prepare("SELECT id FROM reminder_logs WHERE invoice_id=:inv AND channel='email' AND reminder_type=:rt LIMIT 1");
            $already->execute(['inv' => $invoice['id'], 'rt' => $reminderType]);
            if ($already->fetch()) {
                $results['skipped']++;
                continue;
            }

            try {
                $subject = "Reminder: Invoice {$invoice['invoice_number']} due " . $invoice['due_date'];
                $body = "Dear {$invoice['client_name']},<br><br>"
                    . "This is an automated reminder for invoice <strong>{$invoice['invoice_number']}</strong>."
                    . " Outstanding amount: <strong>" . format_currency((float)($invoice['total'] - $invoice['amount_paid']), $invoice['currency']) . "</strong>.<br>"
                    . "Please review your invoice: <a href='" . APP_URL . "/invoices/show/{$invoice['id']}'>View Invoice</a>";
                $ok = $mailService->sendRawHtml($invoice['client_email'], $subject, $body);

                $status = $ok ? 'sent' : 'failed';
                $results[$ok ? 'sent' : 'failed']++;

                $log = $this->db->prepare("INSERT INTO reminder_logs (invoice_id, client_id, channel, reminder_type, status, response_text) VALUES (:invoice_id,:client_id,'email',:reminder_type,:status,:response)");
                $log->execute([
                    'invoice_id' => $invoice['id'],
                    'client_id' => $invoice['client_id'],
                    'reminder_type' => $reminderType,
                    'status' => $status,
                    'response' => $ok ? 'Email sent' : 'Email send failed',
                ]);

                if (WHATSAPP_ENABLED) {
                    $this->logWhatsAppReminder($invoice, $reminderType);
                }
            } catch (Exception $ex) {
                $results['failed']++;
            }
        }

        return $results;
    }

    private function logWhatsAppReminder(array $invoice, string $reminderType): void {
        $already = $this->db->prepare("SELECT id FROM reminder_logs WHERE invoice_id=:inv AND channel='whatsapp' AND reminder_type=:rt LIMIT 1");
        $already->execute(['inv' => $invoice['id'], 'rt' => $reminderType]);
        if ($already->fetch()) return;

        $response = 'WhatsApp webhook not configured';
        $status = 'skipped';

        if (WHATSAPP_WEBHOOK_URL) {
            $payload = json_encode([
                'invoice_number' => $invoice['invoice_number'],
                'invoice_id' => $invoice['id'],
                'reminder_type' => $reminderType,
            ]);
            $ch = curl_init(WHATSAPP_WEBHOOK_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 10,
            ]);
            $resp = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $status = $httpCode >= 200 && $httpCode < 300 ? 'sent' : 'failed';
            $response = $resp ?: ('HTTP ' . $httpCode);
        }

        $log = $this->db->prepare("INSERT INTO reminder_logs (invoice_id, client_id, channel, reminder_type, status, response_text) VALUES (:invoice_id,:client_id,'whatsapp',:reminder_type,:status,:response)");
        $log->execute([
            'invoice_id' => $invoice['id'],
            'client_id' => $invoice['client_id'],
            'reminder_type' => $reminderType,
            'status' => $status,
            'response' => mb_substr($response, 0, 1000),
        ]);
    }

    public function listReminderLogs(): array {
        return $this->db->query("SELECT rl.*, i.invoice_number, c.name AS client_name FROM reminder_logs rl JOIN invoices i ON i.id = rl.invoice_id JOIN clients c ON c.id = rl.client_id ORDER BY rl.sent_at DESC LIMIT 200")->fetchAll();
    }

    public function listCreditNotes(): array {
        $sql = "SELECT cn.*, i.invoice_number, c.name AS client_name FROM credit_notes cn JOIN invoices i ON i.id=cn.invoice_id JOIN clients c ON c.id=cn.client_id ORDER BY cn.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function createCreditNote(array $data): int {
        $number = 'CN-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $stmt = $this->db->prepare("INSERT INTO credit_notes (credit_note_number, invoice_id, client_id, currency, amount, reason, status, issued_at) VALUES (:num,:invoice_id,:client_id,:currency,:amount,:reason,'approved',:issued_at)");
        $stmt->execute([
            'num' => $number,
            'invoice_id' => $data['invoice_id'],
            'client_id' => $data['client_id'],
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'reason' => $data['reason'] ?? null,
            'issued_at' => $data['issued_at'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function applyCreditNote(int $creditNoteId): void {
        $this->db->beginTransaction();
        try {
            $cnStmt = $this->db->prepare("SELECT * FROM credit_notes WHERE id=:id FOR UPDATE");
            $cnStmt->execute(['id' => $creditNoteId]);
            $cn = $cnStmt->fetch();
            if (!$cn || $cn['status'] === 'applied') {
                $this->db->rollBack();
                return;
            }

            $invStmt = $this->db->prepare("SELECT * FROM invoices WHERE id=:id FOR UPDATE");
            $invStmt->execute(['id' => $cn['invoice_id']]);
            $invoice = $invStmt->fetch();
            if (!$invoice) {
                $this->db->rollBack();
                return;
            }

            $newTotal = max(0, (float)$invoice['total'] - (float)$cn['amount']);
            $newTotalBase = $invoice['currency'] === BASE_CURRENCY
                ? $newTotal
                : max(0, (float)$invoice['total_in_base'] - ((float)$cn['amount'] * (float)($invoice['exchange_rate'] ?: 1)));

            $updInv = $this->db->prepare("UPDATE invoices SET total=:total, total_in_base=:total_base WHERE id=:id");
            $updInv->execute(['total' => $newTotal, 'total_base' => $newTotalBase, 'id' => $invoice['id']]);

            $updCn = $this->db->prepare("UPDATE credit_notes SET status='applied', applied_at=NOW() WHERE id=:id");
            $updCn->execute(['id' => $creditNoteId]);

            AuditLog::log('credit_note', $creditNoteId, 'applied', null, ['invoice_id' => $invoice['id'], 'amount' => $cn['amount']]);
            $this->db->commit();
        } catch (Exception $ex) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $ex;
        }
    }

    public function listRecurringTemplates(): array {
        $sql = "SELECT rt.*, c.name AS client_name FROM recurring_templates rt JOIN clients c ON c.id=rt.client_id ORDER BY rt.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function createRecurringTemplate(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO recurring_templates (template_name, client_id, currency, frequency, start_date, next_issue_date, tax_rate, discount_amount, notes, status, items_json) VALUES (:template_name,:client_id,:currency,:frequency,:start_date,:next_issue_date,:tax_rate,:discount_amount,:notes,:status,:items_json)");
        $stmt->execute([
            'template_name' => $data['template_name'],
            'client_id' => $data['client_id'],
            'currency' => $data['currency'],
            'frequency' => $data['frequency'],
            'start_date' => $data['start_date'],
            'next_issue_date' => $data['next_issue_date'],
            'tax_rate' => $data['tax_rate'],
            'discount_amount' => $data['discount_amount'],
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'items_json' => json_encode($data['items']),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function runRecurringGeneration(string $today): array {
        $stmt = $this->db->prepare("SELECT * FROM recurring_templates WHERE status='active' AND next_issue_date <= :today");
        $stmt->execute(['today' => $today]);
        $templates = $stmt->fetchAll();
        $generated = 0;

        foreach ($templates as $tpl) {
            $items = json_decode($tpl['items_json'], true) ?: [];
            if (empty($items)) continue;

            $invoiceId = $this->invoiceService->createInvoice([
                'client_id' => $tpl['client_id'],
                'currency' => $tpl['currency'],
                'tax_rate' => $tpl['tax_rate'],
                'discount_amount' => $tpl['discount_amount'],
                'issue_date' => $today,
                'due_date' => date('Y-m-d', strtotime($today . ' +30 days')),
                'notes' => '[Recurring] ' . ($tpl['notes'] ?? ''),
                'status' => 'sent',
            ], $items);

            $nextDate = $this->calculateNextDate($tpl['next_issue_date'], $tpl['frequency']);
            $upd = $this->db->prepare("UPDATE recurring_templates SET next_issue_date=:next, last_generated_at=NOW() WHERE id=:id");
            $upd->execute(['next' => $nextDate, 'id' => $tpl['id']]);

            AuditLog::log('recurring_template', (int)$tpl['id'], 'invoice_generated', null, ['invoice_id' => $invoiceId]);
            $generated++;
        }

        return ['templates_processed' => count($templates), 'generated' => $generated];
    }

    private function calculateNextDate(string $baseDate, string $frequency): string {
        return match($frequency) {
            'weekly' => date('Y-m-d', strtotime($baseDate . ' +7 days')),
            'monthly' => date('Y-m-d', strtotime($baseDate . ' +1 month')),
            'quarterly' => date('Y-m-d', strtotime($baseDate . ' +3 months')),
            'yearly' => date('Y-m-d', strtotime($baseDate . ' +1 year')),
            default => date('Y-m-d', strtotime($baseDate . ' +1 month')),
        };
    }

    public function listQuotes(): array {
        $sql = "SELECT q.*, c.name AS client_name FROM quotes q JOIN clients c ON c.id=q.client_id ORDER BY q.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function createQuote(array $data, array $items): int {
        $this->db->beginTransaction();
        try {
            $number = 'QT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $subtotal = 0;
            foreach ($items as &$item) {
                $item['amount'] = calculate_item_amount((float)$item['quantity'], (float)$item['unit_price']);
                $subtotal += $item['amount'];
            }
            $totals = calculate_invoice_total($subtotal, (float)$data['tax_rate'], (float)$data['discount_amount']);

            $stmt = $this->db->prepare("INSERT INTO quotes (quote_number, client_id, currency, subtotal, tax_rate, tax_amount, discount_amount, total, status, valid_until, notes) VALUES (:quote_number,:client_id,:currency,:subtotal,:tax_rate,:tax_amount,:discount_amount,:total,:status,:valid_until,:notes)");
            $stmt->execute([
                'quote_number' => $number,
                'client_id' => $data['client_id'],
                'currency' => $data['currency'],
                'subtotal' => $totals['subtotal'],
                'tax_rate' => $totals['tax_rate'],
                'tax_amount' => $totals['tax_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total' => $totals['total'],
                'status' => 'sent',
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $quoteId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare("INSERT INTO quote_items (quote_id, description, quantity, unit, unit_price, amount) VALUES (:quote_id,:description,:quantity,:unit,:unit_price,:amount)");
            foreach ($items as $item) {
                $itemStmt->execute([
                    'quote_id' => $quoteId,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'pcs',
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                ]);
            }

            $this->db->commit();
            return $quoteId;
        } catch (Exception $ex) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $ex;
        }
    }

    public function convertQuoteToInvoice(int $quoteId): ?int {
        $quoteStmt = $this->db->prepare("SELECT * FROM quotes WHERE id=:id LIMIT 1");
        $quoteStmt->execute(['id' => $quoteId]);
        $quote = $quoteStmt->fetch();
        if (!$quote || $quote['status'] === 'converted') return null;

        $itemStmt = $this->db->prepare("SELECT * FROM quote_items WHERE quote_id=:id ORDER BY id ASC");
        $itemStmt->execute(['id' => $quoteId]);
        $quoteItems = $itemStmt->fetchAll();
        if (empty($quoteItems)) return null;

        $items = array_map(function($item) {
            return [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
            ];
        }, $quoteItems);

        $invoiceId = $this->invoiceService->createInvoice([
            'client_id' => $quote['client_id'],
            'currency' => $quote['currency'],
            'tax_rate' => $quote['tax_rate'],
            'discount_amount' => $quote['discount_amount'],
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'notes' => '[Converted from Quote ' . $quote['quote_number'] . '] ' . ($quote['notes'] ?? ''),
            'status' => 'draft',
        ], $items);

        $upd = $this->db->prepare("UPDATE quotes SET status='converted', converted_invoice_id=:invoice_id WHERE id=:id");
        $upd->execute(['invoice_id' => $invoiceId, 'id' => $quoteId]);

        return $invoiceId;
    }

    public function listTaxProfiles(): array {
        return $this->db->query("SELECT * FROM tax_profiles ORDER BY created_at DESC")->fetchAll();
    }

    public function createTaxProfile(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO tax_profiles (name, tax_type, calculation_method, rate, fixed_amount, is_compound, is_active) VALUES (:name,:tax_type,:calculation_method,:rate,:fixed_amount,:is_compound,:is_active)");
        $stmt->execute([
            'name' => $data['name'],
            'tax_type' => $data['tax_type'],
            'calculation_method' => $data['calculation_method'],
            'rate' => $data['rate'],
            'fixed_amount' => $data['fixed_amount'] ?: null,
            'is_compound' => $data['is_compound'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function calculateTaxPreview(float $baseAmount, array $profileIds): array {
        if (empty($profileIds)) return ['base' => $baseAmount, 'tax_total' => 0, 'grand_total' => $baseAmount, 'lines' => []];

        $in = implode(',', array_fill(0, count($profileIds), '?'));
        $stmt = $this->db->prepare("SELECT * FROM tax_profiles WHERE id IN ($in) AND is_active=1 ORDER BY is_compound ASC");
        $stmt->execute($profileIds);
        $profiles = $stmt->fetchAll();

        $lines = [];
        $runningBase = $baseAmount;
        $taxTotal = 0;

        foreach ($profiles as $p) {
            $tax = $p['calculation_method'] === 'fixed'
                ? (float)$p['fixed_amount']
                : round($runningBase * ((float)$p['rate'] / 100), 2);

            $lines[] = ['name' => $p['name'], 'amount' => $tax];
            $taxTotal += $tax;
            if ((int)$p['is_compound'] === 1) {
                $runningBase += $tax;
            }
        }

        return ['base' => $baseAmount, 'tax_total' => $taxTotal, 'grand_total' => $baseAmount + $taxTotal, 'lines' => $lines];
    }

    public function getReconciliationSnapshot(): array {
        $summary = $this->db->query("SELECT provider, COUNT(*) AS tx_count, SUM(amount_in_base) AS provider_total FROM payments WHERE status='success' GROUP BY provider")->fetchAll();

        $mismatchQuery = "
            SELECT i.id AS invoice_id, i.invoice_number, i.total_in_base,
                   COALESCE(SUM(p.amount_in_base),0) AS paid_in_base,
                   ROUND(i.total_in_base - COALESCE(SUM(p.amount_in_base),0), 2) AS variance
            FROM invoices i
            LEFT JOIN payments p ON p.invoice_id = i.id AND p.status='success'
            WHERE i.status IN ('paid','partially_paid','sent','overdue')
            GROUP BY i.id, i.invoice_number, i.total_in_base
            HAVING ABS(variance) > 0.01
            ORDER BY ABS(variance) DESC
            LIMIT 100
        ";
        $mismatches = $this->db->query($mismatchQuery)->fetchAll();

        return ['summary' => $summary, 'mismatches' => $mismatches];
    }

    public function saveAttachment(array $file, string $entityType, int $entityId, string $uploadedByRole): ?int {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;

        $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];
        $mime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        $dir = BASE_PATH . '/storage/attachments';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $finalName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
        $target = $dir . '/' . $finalName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return null;
        }

        $stmt = $this->db->prepare("INSERT INTO attachments (entity_type, entity_id, file_name, file_path, mime_type, file_size, uploaded_by_role) VALUES (:entity_type,:entity_id,:file_name,:file_path,:mime_type,:file_size,:uploaded_by_role)");
        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'file_name' => $safeName,
            'file_path' => 'storage/attachments/' . $finalName,
            'mime_type' => $mime,
            'file_size' => $file['size'],
            'uploaded_by_role' => $uploadedByRole,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function listAttachments(string $entityType, int $entityId): array {
        $stmt = $this->db->prepare("SELECT * FROM attachments WHERE entity_type=:entity_type AND entity_id=:entity_id ORDER BY created_at DESC");
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        return $stmt->fetchAll();
    }

    public function getExportRows(string $exportType): array {
        return match($exportType) {
            'invoices' => $this->db->query("SELECT invoice_number, currency, total, amount_paid, status, issue_date, due_date FROM invoices ORDER BY created_at DESC")->fetchAll(),
            'payments' => $this->db->query("SELECT invoice_id, provider, currency, amount, amount_in_base, status, payment_date FROM payments ORDER BY created_at DESC")->fetchAll(),
            'clients' => $this->db->query("SELECT name, company, email, phone, created_at FROM clients ORDER BY created_at DESC")->fetchAll(),
            default => [],
        };
    }

    public function logExport(string $type, string $format, array $filters, string $role): void {
        $stmt = $this->db->prepare("INSERT INTO export_logs (export_type, format, filters_json, downloaded_by_role) VALUES (:type,:format,:filters,:role)");
        $stmt->execute([
            'type' => $type,
            'format' => $format,
            'filters' => json_encode($filters),
            'role' => $role,
        ]);
    }
}
