<?php
$pageTitle = 'Export Center';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-download me-2"></i>Export Center</h1>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header py-3">Invoices</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= APP_URL ?>/ops/exports/download?type=invoices&format=csv" class="btn btn-outline-primary">Download CSV</a>
                <a href="<?= APP_URL ?>/ops/exports/download?type=invoices&format=xlsx" class="btn btn-outline-secondary">Download XLS</a>
                <a href="<?= APP_URL ?>/ops/exports/api?type=invoices" class="btn btn-light border" target="_blank">Open API JSON</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header py-3">Payments</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= APP_URL ?>/ops/exports/download?type=payments&format=csv" class="btn btn-outline-primary">Download CSV</a>
                <a href="<?= APP_URL ?>/ops/exports/download?type=payments&format=xlsx" class="btn btn-outline-secondary">Download XLS</a>
                <a href="<?= APP_URL ?>/ops/exports/api?type=payments" class="btn btn-light border" target="_blank">Open API JSON</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header py-3">Clients</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= APP_URL ?>/ops/exports/download?type=clients&format=csv" class="btn btn-outline-primary">Download CSV</a>
                <a href="<?= APP_URL ?>/ops/exports/download?type=clients&format=xlsx" class="btn btn-outline-secondary">Download XLS</a>
                <a href="<?= APP_URL ?>/ops/exports/api?type=clients" class="btn btn-light border" target="_blank">Open API JSON</a>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <div class="small text-muted">All export actions are logged with role and filter payload in <code>export_logs</code> table for compliance tracking.</div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
