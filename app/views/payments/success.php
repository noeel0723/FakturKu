<?php $pageTitle = 'Pembayaran Berhasil'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-check-circle" style="font-size:80px;color:#16a34a"></i>
    </div>
    <h2 class="fw-bold text-success mb-3">Pembayaran Berhasil!</h2>
    <p class="text-muted mb-4">Terima kasih. Pembayaran Anda sedang diproses dan akan dikonfirmasi segera.</p>
    <?php if ($invoiceId): ?>
    <a href="<?= APP_URL ?>/invoices/show/<?= (int)$invoiceId ?>" class="btn btn-primary"><i class="bi bi-receipt me-1"></i>Lihat Faktur</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-outline-secondary ms-2"><i class="bi bi-house me-1"></i>Dashboard</a>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
