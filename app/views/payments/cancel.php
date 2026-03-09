<?php $pageTitle = 'Pembayaran Dibatalkan'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-x-circle" style="font-size:80px;color:#dc2626"></i>
    </div>
    <h2 class="fw-bold text-danger mb-3">Pembayaran Dibatalkan</h2>
    <p class="text-muted mb-4">Pembayaran Anda telah dibatalkan. Anda dapat mencoba kembali kapan saja.</p>
    <?php if ($invoiceId): ?>
    <a href="<?= APP_URL ?>/invoices/show/<?= (int)$invoiceId ?>" class="btn btn-primary"><i class="bi bi-receipt me-1"></i>Kembali ke Invoice</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-outline-secondary ms-2"><i class="bi bi-house me-1"></i>Dashboard</a>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
