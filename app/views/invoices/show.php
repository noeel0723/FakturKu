<?php
$pageTitle = 'Invoice ' . $invoice['invoice_number'];
require BASE_PATH . '/app/views/layouts/header.php';
$badgeClass = match($invoice['status']) {
    'paid' => 'bg-success', 'sent' => 'bg-primary',
    'partially_paid' => 'bg-warning text-dark', 'overdue' => 'bg-danger',
    'cancelled' => 'bg-secondary', default => 'bg-secondary',
};
$remaining = max(0, (float)$invoice['total'] - (float)$invoice['amount_paid']);
?>

<div class="page-header">
    <h1>
        <i class="bi bi-receipt me-2"></i><?= e($invoice['invoice_number']) ?>
        <span class="badge badge-status <?= $badgeClass ?> ms-2"><?= ucfirst(str_replace('_',' ',$invoice['status'])) ?></span>
    </h1>
    <div class="d-flex gap-2">
        <?php if (in_array($invoice['status'], ['draft','sent','partially_paid'])): ?>
        <a href="<?= APP_URL ?>/invoices/edit/<?= $invoice['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/invoices/pdf/<?= $invoice['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-pdf me-1"></i>PDF</a>
        <form method="POST" action="<?= APP_URL ?>/invoices/send/<?= $invoice['id'] ?>" class="d-inline">
            <button class="btn btn-outline-success btn-sm"><i class="bi bi-envelope me-1"></i>Kirim Email</button>
        </form>
        <a href="<?= APP_URL ?>/invoices" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <!-- Invoice Info -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Dari</h6>
                        <div class="fw-semibold"><?= e(COMPANY_NAME) ?></div>
                        <div class="text-muted small"><?= e(COMPANY_ADDRESS) ?></div>
                        <div class="text-muted small"><?= e(COMPANY_EMAIL) ?></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Kepada</h6>
                        <div class="fw-semibold"><?= e($invoice['client_name']) ?></div>
                        <div class="text-muted small"><?= e($invoice['client_company'] ?? '') ?></div>
                        <div class="text-muted small"><?= e($invoice['client_address'] ?? '') ?></div>
                        <div class="text-muted small"><?= e($invoice['client_email']) ?></div>
                    </div>
                </div>
                <hr>
                <div class="row text-sm">
                    <div class="col-md-3"><span class="text-muted">Tanggal:</span><br><strong><?= format_date($invoice['issue_date']) ?></strong></div>
                    <div class="col-md-3"><span class="text-muted">Jatuh Tempo:</span><br><strong><?= format_date($invoice['due_date']) ?></strong></div>
                    <div class="col-md-3"><span class="text-muted">Mata Uang:</span><br><strong><?= e($invoice['currency']) ?></strong></div>
                    <?php if ($invoice['currency'] !== BASE_CURRENCY && $invoice['exchange_rate']): ?>
                    <div class="col-md-3"><span class="text-muted">Kurs:</span><br><strong>1 <?= e($invoice['currency']) ?> = <?= number_format((float)$invoice['exchange_rate'],4) ?> <?= BASE_CURRENCY ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="card mb-3">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:40px">No</th>
                            <th>Deskripsi</th>
                            <th class="text-center" style="width:80px">Qty</th>
                            <th class="text-end" style="width:130px">Harga</th>
                            <th class="text-end" style="width:130px">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoice['items'] as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($item['description']) ?></td>
                            <td class="text-center"><?= $item['quantity'] ?> <?= e($item['unit']) ?></td>
                            <td class="text-end"><?= format_currency((float)$item['unit_price'], $invoice['currency']) ?></td>
                            <td class="text-end"><?= format_currency((float)$item['amount'], $invoice['currency']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($invoice['notes']): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="text-muted">Catatan</h6>
                <p class="mb-0"><?= nl2br(e($invoice['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header py-3">
                <i class="bi bi-clock-history me-1"></i>Riwayat Pembayaran
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Provider</th>
                            <th>Metode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoice['payments'])): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada pembayaran</td></tr>
                        <?php else: ?>
                        <?php foreach ($invoice['payments'] as $pmt): ?>
                        <tr>
                            <td><?= format_date($pmt['payment_date']) ?></td>
                            <td>
                                <?= format_currency((float)$pmt['amount'], $pmt['currency']) ?>
                                <?php if ($pmt['currency'] !== BASE_CURRENCY && $pmt['amount_in_base']): ?>
                                <br><small class="text-muted">(<?= format_currency((float)$pmt['amount_in_base'], BASE_CURRENCY) ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= e($pmt['provider']) ?></span></td>
                            <td><?= e($pmt['payment_method'] ?? '-') ?></td>
                            <td>
                                <?php $pBadge = match($pmt['status']){ 'success'=>'bg-success','pending'=>'bg-warning text-dark','failed'=>'bg-danger',default=>'bg-secondary' }; ?>
                                <span class="badge <?= $pBadge ?>"><?= ucfirst($pmt['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right sidebar: Summary & Actions -->
    <div class="col-md-4">
        <div class="card mb-3 sticky-top" style="top:20px">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span><?= format_currency((float)$invoice['subtotal'], $invoice['currency']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Pajak (<?= $invoice['tax_rate'] ?>%)</span>
                    <span><?= format_currency((float)$invoice['tax_amount'], $invoice['currency']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Diskon</span>
                    <span>-<?= format_currency((float)$invoice['discount_amount'], $invoice['currency']) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold fs-5">TOTAL</span>
                    <span class="fw-bold fs-5 text-primary"><?= format_currency((float)$invoice['total'], $invoice['currency']) ?></span>
                </div>
                <?php if ($invoice['currency'] !== BASE_CURRENCY && $invoice['total_in_base']): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total (<?= BASE_CURRENCY ?>)</span>
                    <span class="small"><?= format_currency((float)$invoice['total_in_base'], BASE_CURRENCY) ?></span>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-success">Terbayar</span>
                    <span class="text-success"><?= format_currency((float)$invoice['amount_paid'], $invoice['currency']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold text-danger">Sisa</span>
                    <span class="fw-bold text-danger"><?= format_currency($remaining, $invoice['currency']) ?></span>
                </div>

                <?php if ($remaining > 0 && !in_array($invoice['status'], ['cancelled','paid'])): ?>
                <!-- Online Payment -->
                <form method="POST" action="<?= APP_URL ?>/payments/checkout" class="mb-2">
                    <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-credit-card me-1"></i>Bayar Online (<?= strtoupper(PAYMENT_PROVIDER) ?>)
                    </button>
                </form>

                <!-- Manual Payment -->
                <button class="btn btn-outline-primary w-100 mb-3" data-bs-toggle="collapse" data-bs-target="#manualPayment">
                    <i class="bi bi-cash me-1"></i>Catat Pembayaran Manual
                </button>
                <div class="collapse" id="manualPayment">
                    <form method="POST" action="<?= APP_URL ?>/payments/record/<?= $invoice['id'] ?>">
                        <div class="mb-2">
                            <label class="form-label small">Jumlah</label>
                            <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="0.01" value="<?= $remaining ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Mata Uang</label>
                            <select name="currency" class="form-select form-select-sm">
                                <?php foreach ($currencies as $cur): ?>
                                <option value="<?= e($cur['code']) ?>" <?= $invoice['currency'] === $cur['code'] ? 'selected' : '' ?>>
                                    <?= e($cur['code']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Metode</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="bank_transfer">Transfer Bank</option>
                                <option value="cash">Tunai</option>
                                <option value="check">Cek/Giro</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Tanggal</label>
                            <input type="date" name="payment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Catatan</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="No. referensi, dll.">
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-check-lg me-1"></i>Simpan Pembayaran</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
