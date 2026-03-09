<?php $pageTitle = 'Menunggu Pembayaran'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-hourglass-split" style="font-size:80px;color:#f59e0b"></i>
    </div>
    <h2 class="fw-bold text-warning mb-3">Menunggu Pembayaran</h2>
    <p class="text-muted mb-4">Pembayaran Anda sedang diproses. Silakan tunggu konfirmasi dari payment gateway.</p>
    <?php if ($invoiceId): ?>
    <a href="<?= APP_URL ?>/invoices/show/<?= (int)$invoiceId ?>" class="btn btn-primary"><i class="bi bi-receipt me-1"></i>Cek Status Invoice</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-outline-secondary ms-2"><i class="bi bi-house me-1"></i>Dashboard</a>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
