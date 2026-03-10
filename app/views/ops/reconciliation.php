<?php
$pageTitle = 'Reconciliation Dashboard';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-shield-check me-2"></i>Reconciliation Dashboard</h1>
</div>

<div class="card mb-3">
    <div class="card-header py-3">Gateway Summary (Success Transactions)</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Transaction Count</th>
                    <th class="text-end">Total in <?= e(BASE_CURRENCY) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">No paid transactions yet.</td></tr>
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
    <div class="card-header py-3">Invoice Variance (Potential Mismatch)</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th class="text-end">Invoice Total (Base)</th>
                    <th class="text-end">Paid (Base)</th>
                    <th class="text-end">Variance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mismatches)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No mismatch detected.</td></tr>
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
