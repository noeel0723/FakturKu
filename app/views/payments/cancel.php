<?php $pageTitle = 'Payment Cancelled'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-x-circle" style="font-size:80px;color:#dc2626"></i>
    </div>
    <h2 class="fw-bold text-danger mb-3">Payment Cancelled</h2>
    <p class="text-muted mb-4">Your payment has been cancelled. You can retry anytime.</p>
    <?php if ($invoiceId): ?>
    <a href="<?= APP_URL ?>/invoices/show/<?= (int)$invoiceId ?>" class="btn btn-primary"><i class="bi bi-receipt me-1"></i>Back to Invoice</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-outline-secondary ms-2"><i class="bi bi-house me-1"></i>Dashboard</a>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
