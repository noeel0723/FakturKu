<?php
$pageTitle = 'Dashboard Rekonsiliasi';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-shield-check me-2"></i>Dashboard Rekonsiliasi</h1>
</div>

<div class="card mb-3">
    <div class="card-header py-3">Ringkasan Gateway (Transaksi Berhasil)</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Penyedia</th>
                    <th>Jumlah Transaksi</th>
                    <th class="text-end">Total dalam <?= e(BASE_CURRENCY) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada transaksi berhasil.</td></tr>
                <?php else: ?>
                <?php foreach ($summary as $row): ?>
                <tr>
                    <td><?= e($row['provider']) ?></td>
                    <td><?= (int)$row['tx_count'] ?></td>
                    <td class="text-end"><?= format_currency((float)$row['provider_total'], BASE_CURRENCY) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header py-3">Selisih Faktur (Potensi Ketidaksesuaian)</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Faktur</th>
                    <th class="text-end">Total Faktur (Dasar)</th>
                    <th class="text-end">Dibayar (Dasar)</th>
                    <th class="text-end">Selisih</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mismatches)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada ketidaksesuaian terdeteksi.</td></tr>
                <?php else: ?>
                <?php foreach ($mismatches as $mis): ?>
                <tr>
                    <td><a href="<?= APP_URL ?>/invoices/show/<?= (int)$mis['invoice_id'] ?>"><?= e($mis['invoice_number']) ?></a></td>
                    <td class="text-end"><?= format_currency((float)$mis['total_in_base'], BASE_CURRENCY) ?></td>
                    <td class="text-end"><?= format_currency((float)$mis['paid_in_base'], BASE_CURRENCY) ?></td>
                    <td class="text-end <?= ((float)$mis['variance'] < 0) ? 'text-danger' : 'text-warning' ?>"><?= format_currency((float)$mis['variance'], BASE_CURRENCY) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
