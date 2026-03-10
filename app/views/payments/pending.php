<?php $pageTitle = 'Payment Pending'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-hourglass-split" style="font-size:80px;color:#f59e0b"></i>
    </div>
    <h2 class="fw-bold text-warning mb-3">Payment Pending</h2>
    <p class="text-muted mb-4">Your payment is being processed. Please wait for confirmation from the payment gateway.</p>
    <?php if ($invoiceId): ?>
    <a href="<?= APP_URL ?>/invoices/show/<?= (int)$invoiceId ?>" class="btn btn-primary"><i class="bi bi-receipt me-1"></i>Check Invoice Status</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-outline-secondary ms-2"><i class="bi bi-house me-1"></i>Dashboard</a>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
