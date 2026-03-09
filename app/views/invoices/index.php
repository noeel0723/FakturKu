<?php $pageTitle = 'Invoices'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

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
    <h1><i class="bi bi-receipt me-2"></i>Invoices</h1>
    <a href="<?= APP_URL ?>/invoices/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Invoice</a>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="<?= APP_URL ?>/invoices" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by invoice number or client" value="<?= e($search ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="paid" <?= ($statusFilter ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="sent" <?= ($statusFilter ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="partially_paid" <?= ($statusFilter ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="unpaid" <?= ($statusFilter ?? '') === 'unpaid' ? 'selected' : '' ?>>Unpaid (Sent + Partial)</option>
                    <option value="overdue" <?= ($statusFilter ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    <option value="draft" <?= ($statusFilter ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="cancelled" <?= ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Apply</button>
                <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/invoices">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4" style="border-color:#dbe4eb;">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="p-3" style="border:1px solid #d9ece6; border-radius:12px; background:#f1fbf8;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Paid</div>
                            <div class="small text-muted">Count: <?= $statusCounts['paid'] ?></div>
                        </div>
                        <i class="bi bi-check-circle-fill" style="color:#26b896; font-size:22px;"></i>
                    </div>
                    <div class="small mt-2" style="color:#26b896;">Value: <?= format_currency($statusValues['paid'], BASE_CURRENCY) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3" style="border:1px solid #f0e5c8; border-radius:12px; background:#fffaf0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Unpaid</div>
                            <div class="small text-muted">Count: <?= $statusCounts['sent'] + $statusCounts['partially_paid'] ?></div>
                        </div>
                        <i class="bi bi-exclamation-circle-fill" style="color:#efb341; font-size:22px;"></i>
                    </div>
                    <div class="small mt-2" style="color:#b9851d;">Value: <?= format_currency($statusValues['sent'] + $statusValues['partially_paid'], BASE_CURRENCY) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3" style="border:1px solid #f2dada; border-radius:12px; background:#fff4f4;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Overdue</div>
                            <div class="small text-muted">Count: <?= $statusCounts['overdue'] ?></div>
                        </div>
                        <i class="bi bi-bell-fill" style="color:#d26b6b; font-size:22px;"></i>
                    </div>
                    <div class="small mt-2" style="color:#bb5a5a;">Value: <?= format_currency($statusValues['overdue'], BASE_CURRENCY) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3" style="border:1px solid #d8e3ef; border-radius:12px; background:#f2f7fc;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Draft</div>
                            <div class="small text-muted">Count: <?= $statusCounts['draft'] ?></div>
                        </div>
                        <i class="bi bi-file-earmark-text-fill" style="color:#6d8aa8; font-size:22px;"></i>
                    </div>
                    <div class="small mt-2" style="color:#5e7f9f;">Value: <?= format_currency($statusValues['draft'], BASE_CURRENCY) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header py-2" style="background:#fbfcfd;">
        <div class="d-flex flex-wrap gap-2">
            <a class="badge <?= empty($statusFilter) ? 'text-bg-primary' : 'text-bg-light border text-decoration-none text-dark' ?>" href="<?= APP_URL ?>/invoices?search=<?= urlencode($search ?? '') ?>">All (<?= $statusCounts['all'] ?>)</a>
            <a class="badge <?= ($statusFilter ?? '') === 'paid' ? 'text-bg-success' : 'text-bg-light border text-decoration-none text-dark' ?>" href="<?= APP_URL ?>/invoices?status=paid&search=<?= urlencode($search ?? '') ?>">Paid (<?= $statusCounts['paid'] ?>)</a>
            <a class="badge <?= ($statusFilter ?? '') === 'unpaid' ? 'text-bg-warning' : 'text-bg-light border text-decoration-none text-dark' ?>" href="<?= APP_URL ?>/invoices?status=unpaid&search=<?= urlencode($search ?? '') ?>">Unpaid (<?= $statusCounts['sent'] + $statusCounts['partially_paid'] ?>)</a>
            <a class="badge <?= ($statusFilter ?? '') === 'overdue' ? 'text-bg-danger' : 'text-bg-light border text-decoration-none text-dark' ?>" href="<?= APP_URL ?>/invoices?status=overdue&search=<?= urlencode($search ?? '') ?>">Overdue (<?= $statusCounts['overdue'] ?>)</a>
            <a class="badge <?= ($statusFilter ?? '') === 'draft' ? 'text-bg-secondary' : 'text-bg-light border text-decoration-none text-dark' ?>" href="<?= APP_URL ?>/invoices?status=draft&search=<?= urlencode($search ?? '') ?>">Draft (<?= $statusCounts['draft'] ?>)</a>
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
                    <th class="text-end">Paid</th>
                    <th>Status</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No invoices found.</td></tr>
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
                        <a href="<?= APP_URL ?>/invoices/show/<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
