<?php
$pageTitle = 'Nota Kredit';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-receipt-cutoff me-2"></i>Nota Kredit</h1>
</div>

<div class="card mb-3">
    <div class="card-header py-3">Buat Nota Kredit</div>
    <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/ops/credit-notes/store" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Faktur</label>
                <select name="invoice_id" class="form-select" required>
                    <option value="">Pilih faktur</option>
                    <?php foreach ($invoices as $inv): ?>
                    <option value="<?= (int)$inv['id'] ?>"><?= e($inv['invoice_number']) ?> - <?= e($inv['client_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Jumlah</label>
                <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Diterbitkan Pada</label>
                <input type="date" name="issued_at" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Alasan</label>
                <input type="text" name="reason" class="form-control" placeholder="Alasan penyesuaian / pengembalian">
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Buat Nota Kredit</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header py-3">Daftar Nota Kredit</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Faktur</th>
                    <th>Klien</th>
                    <th>Diterbitkan</th>
                    <th class="text-end">Jumlah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($creditNotes)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada nota kredit dibuat.</td></tr>
                <?php else: ?>
                <?php foreach ($creditNotes as $cn): ?>
                <tr>
                    <td><?= e($cn['credit_note_number']) ?></td>
                    <td><?= e($cn['invoice_number']) ?></td>
                    <td><?= e($cn['client_name']) ?></td>
                    <td><?= format_date($cn['issued_at']) ?></td>
                    <td class="text-end"><?= format_currency((float)$cn['amount'], $cn['currency']) ?></td>
                    <td><span class="badge <?= $cn['status'] === 'applied' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($cn['status']) ?></span></td>
                    <td>
                        <?php if ($cn['status'] !== 'applied'): ?>
                        <form method="POST" action="<?= APP_URL ?>/ops/credit-notes/apply/<?= (int)$cn['id'] ?>">
                            <button class="btn btn-sm btn-outline-primary">Terapkan</button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small">Diterapkan</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
