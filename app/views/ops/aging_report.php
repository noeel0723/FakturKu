<?php
$pageTitle = 'Laporan Umur Piutang';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-bar-chart-line me-2"></i>Laporan Umur Piutang</h1>
    <form method="GET" action="<?= APP_URL ?>/ops/aging-report" class="d-flex gap-2">
        <input type="date" class="form-control" name="as_of" value="<?= e($asOfDate) ?>">
        <button class="btn btn-primary" type="submit">Perbarui</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <?php foreach ($buckets as $bucket): ?>
    <div class="col-md-3 col-6">
        <div class="stat-pill h-100">
            <div class="small text-muted"><?= e($bucket['label']) ?></div>
            <div class="fw-bold fs-5 mb-1"><?= (int)$bucket['count'] ?> faktur</div>
            <div class="text-muted"><?= format_currency((float)$bucket['amount'], BASE_CURRENCY) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header py-3">Faktur Belum Dibayar per <?= format_date($asOfDate) ?></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Faktur</th>
                    <th>Klien</th>
                    <th>Tanggal Jatuh Tempo</th>
                    <th>Hari Keterlambatan</th>
                    <th class="text-end">Belum Dibayar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada faktur belum dibayar di periode ini.</td></tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><a href="<?= APP_URL ?>/invoices/show/<?= (int)$row['id'] ?>"><?= e($row['invoice_number']) ?></a></td>
                    <td><?= e($row['client_name']) ?></td>
                    <td><?= format_date($row['due_date']) ?></td>
                    <td><?= (int)$row['overdue_days'] ?></td>
                    <td class="text-end fw-semibold"><?= format_currency((float)$row['outstanding'], $row['currency']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
