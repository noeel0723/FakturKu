<?php
$pageTitle = 'Faktur ' . $invoice['invoice_number'];
require BASE_PATH . '/app/views/layouts/header.php';

$statusClass = match($invoice['status']) {
    'paid' => 'text-bg-success',
    'sent' => 'text-bg-primary',
    'partially_paid' => 'text-bg-warning',
    'overdue' => 'text-bg-danger',
    'cancelled' => 'text-bg-secondary',
    default => 'text-bg-secondary',
};

$remaining = max(0, (float)$invoice['total'] - (float)$invoice['amount_paid']);
$attachments = $attachments ?? [];
?>

<div class="page-header">
    <h1>
        <i class="bi bi-receipt-cutoff me-2"></i>
        Detail Faktur
    </h1>
    <div class="d-flex gap-2">
        <?php if (in_array($invoice['status'], ['draft','sent','partially_paid'])): ?>
        <a href="<?= APP_URL ?>/invoices/edit/<?= $invoice['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/invoices/pdf/<?= $invoice['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
        <form method="POST" action="<?= APP_URL ?>/invoices/send/<?= $invoice['id'] ?>" class="d-inline">
            <button class="btn btn-outline-success btn-sm"><i class="bi bi-envelope me-1"></i>Kirim Email</button>
        </form>
        <a href="<?= APP_URL ?>/invoices" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="card mb-4" style="border-color:#dbe4eb;">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <div class="small text-muted">Nomor Faktur</div>
                <div class="fw-bold fs-5"><?= e($invoice['invoice_number']) ?></div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Tanggal Terbit</div>
                <div class="fw-semibold"><?= format_date($invoice['issue_date']) ?></div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Tanggal Jatuh Tempo</div>
                <div class="fw-semibold"><?= format_date($invoice['due_date']) ?></div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Mata Uang</div>
                <div class="fw-semibold"><?= e($invoice['currency']) ?></div>
            </div>
            <div class="col-md-3 text-md-end">
                <span class="badge badge-status <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $invoice['status'])) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-1 text-muted">Dari</h6>
                        <div class="fw-semibold"><?= e(COMPANY_NAME) ?></div>
                        <div class="small text-muted"><?= e(COMPANY_ADDRESS) ?></div>
                        <div class="small text-muted"><?= e(COMPANY_EMAIL) ?></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-1 text-muted">Tagihan Ke</h6>
                        <div class="fw-semibold"><?= e($invoice['client_name']) ?></div>
                        <div class="small text-muted"><?= e($invoice['client_company'] ?: '-') ?></div>
                        <div class="small text-muted"><?= e($invoice['client_address'] ?: '-') ?></div>
                        <div class="small text-muted"><?= e($invoice['client_email']) ?></div>
                    </div>
                </div>
                <?php if ($invoice['currency'] !== BASE_CURRENCY && $invoice['exchange_rate']): ?>
                <div class="mt-3 p-3" style="background:#f4f7fa; border:1px solid #dde6ee; border-radius:10px;">
                    <div class="small text-muted mb-1">Snapshot Kurs Tukar</div>
                    <strong>1 <?= e($invoice['currency']) ?> = <?= number_format((float)$invoice['exchange_rate'], 4) ?> <?= BASE_CURRENCY ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-3">Item Faktur</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Deskripsi</th>
                            <th class="text-center" style="width:120px">Kuantitas</th>
                            <th class="text-end" style="width:160px">Harga Satuan</th>
                            <th class="text-end" style="width:160px">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoice['items'] as $idx => $item): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= e($item['description']) ?></td>
                            <td class="text-center"><?= e($item['quantity']) ?> <?= e($item['unit']) ?></td>
                            <td class="text-end"><?= format_currency((float)$item['unit_price'], $invoice['currency']) ?></td>
                            <td class="text-end fw-semibold"><?= format_currency((float)$item['amount'], $invoice['currency']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-1"></i>Riwayat Pembayaran</span>
                <span class="small text-muted">Diperbarui otomatis dari catatan gateway/manual</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Penyedia</th>
                            <th>Metode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoice['payments'])): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pembayaran dicatat.</td></tr>
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
                            <td><span class="badge text-bg-light border"><?= e($pmt['provider']) ?></span></td>
                            <td><?= e($pmt['payment_method'] ?: '-') ?></td>
                            <td>
                                <?php $pBadge = match($pmt['status']) { 'success' => 'text-bg-success', 'pending' => 'text-bg-warning', 'failed' => 'text-bg-danger', default => 'text-bg-secondary' }; ?>
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

    <div class="col-lg-4">
        <div class="card mb-3 sticky-top" style="top:18px;">
            <div class="card-header py-3">Ringkasan Faktur</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span><?= format_currency((float)$invoice['subtotal'], $invoice['currency']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Pajak (<?= e($invoice['tax_rate']) ?>%)</span>
                    <span><?= format_currency((float)$invoice['tax_amount'], $invoice['currency']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Diskon</span>
                    <span>-<?= format_currency((float)$invoice['discount_amount'], $invoice['currency']) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Total</span>
                    <span class="fw-bold fs-5" style="color:#2A384C;"><?= format_currency((float)$invoice['total'], $invoice['currency']) ?></span>
                </div>
                <?php if ($invoice['currency'] !== BASE_CURRENCY && $invoice['total_in_base']): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small text-muted">Total (<?= BASE_CURRENCY ?>)</span>
                    <span class="small"><?= format_currency((float)$invoice['total_in_base'], BASE_CURRENCY) ?></span>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-success">Dibayar</span>
                    <span class="text-success"><?= format_currency((float)$invoice['amount_paid'], $invoice['currency']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-danger fw-semibold">Sisa</span>
                    <span class="text-danger fw-semibold"><?= format_currency($remaining, $invoice['currency']) ?></span>
                </div>

                <?php if (!empty($invoice['notes'])): ?>
                <div class="p-3 mb-3" style="border:1px solid #dee7ee; border-radius:10px; background:#f8fbfd;">
                    <div class="small text-muted mb-1">Catatan</div>
                    <div class="small"><?= nl2br(e($invoice['notes'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($remaining > 0 && !in_array($invoice['status'], ['cancelled', 'paid'])): ?>
                <form method="POST" action="<?= APP_URL ?>/payments/checkout" class="mb-2">
                    <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-credit-card me-1"></i>Bayar Online (<?= strtoupper(PAYMENT_PROVIDER) ?>)
                    </button>
                </form>

                <button class="btn btn-outline-primary w-100 mb-3" data-bs-toggle="collapse" data-bs-target="#manualPayment">
                    <i class="bi bi-cash-coin me-1"></i>Catat Pembayaran Manual
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
                                <option value="<?= e($cur['code']) ?>" <?= $invoice['currency'] === $cur['code'] ? 'selected' : '' ?>><?= e($cur['code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Metode</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="bank_transfer">Transfer Bank</option>
                                <option value="cash">Tunai</option>
                                <option value="check">Cek</option>
                                <option value="e_wallet">Dompet Digital</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Tanggal Pembayaran</label>
                            <input type="date" name="payment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Catatan Referensi</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Ref transfer, channel, dll.">
                        </div>
                            <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-check2-circle me-1"></i>Simpan Pembayaran</button>
                    </form>
                </div>
                <?php endif; ?>

                <hr>
                <h6 class="mb-3">Lampiran</h6>
                <form method="POST" action="<?= APP_URL ?>/ops/attachments/upload" enctype="multipart/form-data" class="mb-3">
                    <input type="hidden" name="entity_type" value="invoice">
                    <input type="hidden" name="entity_id" value="<?= (int)$invoice['id'] ?>">
                    <input type="file" name="attachment" class="form-control form-control-sm mb-2" accept=".pdf,image/png,image/jpeg,image/webp" required>
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-paperclip me-1"></i>Unggah Lampiran
                    </button>
                </form>

                <?php if (empty($attachments)): ?>
                <div class="small text-muted">Belum ada lampiran diunggah.</div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($attachments as $att): ?>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <div class="small text-truncate" style="max-width:200px;" title="<?= e($att['file_name']) ?>">
                            <i class="bi bi-file-earmark me-1"></i><?= e($att['file_name']) ?>
                        </div>
                        <a href="<?= APP_URL . '/' . ltrim($att['file_path'], '/') ?>" target="_blank" class="btn btn-sm btn-light border">Lihat</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
