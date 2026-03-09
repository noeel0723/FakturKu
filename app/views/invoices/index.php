<?php $pageTitle = 'Daftar Invoice'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<?php
$statusCounts = [
    'all' => count($invoices),
    'paid' => 0,
    'sent' => 0,
    'partially_paid' => 0,
    'overdue' => 0,
    'draft' => 0,
];

$statusValues = [
    'paid' => 0,
    'sent' => 0,
    'partially_paid' => 0,
    'overdue' => 0,
    'draft' => 0,
];

foreach ($invoices as $item) {
    $st = $item['status'];
    if (isset($statusCounts[$st])) {
        $statusCounts[$st]++;
        $statusValues[$st] += (float)$item['total'];
    }
}
?>

<div class="page-header">
    <h1><i class="bi bi-receipt me-2"></i>Invoice</h1>
    <a href="<?= APP_URL ?>/invoices/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Invoice</a>
</div>

<div class="card mb-4" style="border-color:#dbe4eb;">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="p-3" style="border:1px solid #d9ece6; border-radius:12px; background:#f1fbf8;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Paid</div>
                            <div class="small text-muted">Jumlah: <?= $statusCounts['paid'] ?></div>
                        </div>
                        <i class="bi bi-check-circle-fill" style="color:#26b896; font-size:22px;"></i>
                    </div>
                    <div class="small mt-2" style="color:#26b896;">Nilai: <?= format_currency($statusValues['paid'], BASE_CURRENCY) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3" style="border:1px solid #f0e5c8; border-radius:12px; background:#fffaf0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Unpaid</div>
                            <div class="small text-muted">Jumlah: <?= $statusCounts['sent'] + $statusCounts['partially_paid'] ?></div>
                        </div>
                        <i class="bi bi-exclamation-circle-fill" style="color:#efb341; font-size:22px;"></i>
                    </div>
                    <div class="small mt-2" style="color:#b9851d;">Nilai: <?= format_currency($statusValues['sent'] + $statusValues['partially_paid'], BASE_CURRENCY) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3" style="border:1px solid #f2dada; border-radius:12px; background:#fff4f4;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Overdue</div>
                            <div class="small text-muted">Jumlah: <?= $statusCounts['overdue'] ?></div>
                        </div>
                        <i class="bi bi-bell-fill" style="color:#d26b6b; font-size:22px;"></i>
                    </div>
                    <div class="small mt-2" style="color:#bb5a5a;">Nilai: <?= format_currency($statusValues['overdue'], BASE_CURRENCY) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3" style="border:1px solid #d8e3ef; border-radius:12px; background:#f2f7fc;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Draft</div>
                            <div class="small text-muted">Jumlah: <?= $statusCounts['draft'] ?></div>
                        </div>
                        <i class="bi bi-file-earmark-text-fill" style="color:#6d8aa8; font-size:22px;"></i>
                    </div>
                    <div class="small mt-2" style="color:#5e7f9f;">Nilai: <?= format_currency($statusValues['draft'], BASE_CURRENCY) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header py-2" style="background:#fbfcfd;">
        <div class="d-flex flex-wrap gap-2">
            <span class="badge text-bg-light border">All (<?= $statusCounts['all'] ?>)</span>
            <span class="badge text-bg-light border">Paid (<?= $statusCounts['paid'] ?>)</span>
            <span class="badge text-bg-light border">Unpaid (<?= $statusCounts['sent'] + $statusCounts['partially_paid'] ?>)</span>
            <span class="badge text-bg-light border">Overdue (<?= $statusCounts['overdue'] ?>)</span>
            <span class="badge text-bg-light border">Draft (<?= $statusCounts['draft'] ?>)</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Client / Invoice</th>
                    <th>Create</th>
                    <th>Due</th>
                    <th>Currency</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Terbayar</th>
                    <th>Status</th>
                    <th style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Belum ada invoice</td></tr>
                <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>/invoices/show/<?= $inv['id'] ?>" class="fw-semibold text-decoration-none"><?= e($inv['invoice_number']) ?></a>
                        <div class="small text-muted"><?= e($inv['client_name']) ?></div>
                    </td>
                    <td><?= format_date($inv['issue_date']) ?></td>
                    <td><?= format_date($inv['due_date']) ?></td>
                    <td><span class="badge text-bg-light border"><?= e($inv['currency']) ?></span></td>
                    <td class="text-end"><?= format_currency((float)$inv['total'], $inv['currency']) ?></td>
                    <td class="text-end"><?= format_currency((float)$inv['amount_paid'], $inv['currency']) ?></td>
                    <td>
                        <?php
                        $badgeClass = match($inv['status']) {
                            'paid' => 'text-bg-success', 'sent' => 'text-bg-primary',
                            'partially_paid' => 'text-bg-warning', 'overdue' => 'text-bg-danger',
                            'cancelled' => 'text-bg-secondary', default => 'text-bg-secondary',
                        };
                        ?>
                        <span class="badge badge-status <?= $badgeClass ?>"><?= ucfirst(str_replace('_',' ',$inv['status'])) ?></span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/invoices/show/<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary" title="Lihat"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
