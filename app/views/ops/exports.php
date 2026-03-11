<?php
$pageTitle = 'Pusat Ekspor';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-download me-2"></i>Pusat Ekspor</h1>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header py-3">Faktur</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= APP_URL ?>/ops/exports/download?type=invoices&format=csv" class="btn btn-outline-primary">Unduh CSV</a>
                <a href="<?= APP_URL ?>/ops/exports/download?type=invoices&format=xlsx" class="btn btn-outline-secondary">Unduh XLS</a>
                <a href="<?= APP_URL ?>/ops/exports/api?type=invoices" class="btn btn-light border" target="_blank">Buka API JSON</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header py-3">Pembayaran</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= APP_URL ?>/ops/exports/download?type=payments&format=csv" class="btn btn-outline-primary">Unduh CSV</a>
                <a href="<?= APP_URL ?>/ops/exports/download?type=payments&format=xlsx" class="btn btn-outline-secondary">Unduh XLS</a>
                <a href="<?= APP_URL ?>/ops/exports/api?type=payments" class="btn btn-light border" target="_blank">Buka API JSON</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header py-3">Klien</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= APP_URL ?>/ops/exports/download?type=clients&format=csv" class="btn btn-outline-primary">Unduh CSV</a>
                <a href="<?= APP_URL ?>/ops/exports/download?type=clients&format=xlsx" class="btn btn-outline-secondary">Unduh XLS</a>
                <a href="<?= APP_URL ?>/ops/exports/api?type=clients" class="btn btn-light border" target="_blank">Buka API JSON</a>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <div class="small text-muted">Semua tindakan ekspor dicatat dengan peran dan payload filter di tabel <code>export_logs</code> untuk pelacakan kepatuhan.</div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
