<?php
$pageTitle = 'Mesin Pengingat';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-bell me-2"></i>Mesin Pengingat</h1>
    <form method="POST" action="<?= APP_URL ?>/ops/reminders/run" class="d-inline">
        <button class="btn btn-primary"><i class="bi bi-play-circle me-1"></i>Jalankan Job Pengingat</button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="mb-0 text-muted">Set aturan saat ini: kirim pada D-7, D-1, dan D+3 dari tanggal jatuh tempo untuk faktur belum dibayar. Email berjalan otomatis di job ini dan log WhatsApp berjalan jika diaktifkan.</p>
    </div>
</div>

<div class="card">
    <div class="card-header py-3">Log Pengingat</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Faktur</th>
                    <th>Klien</th>
                    <th>Channel</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Respons</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada log pengingat.</td></tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['sent_at']) ?></td>
                    <td><?= e($log['invoice_number']) ?></td>
                    <td><?= e($log['client_name']) ?></td>
                    <td><span class="badge text-bg-light border"><?= e($log['channel']) ?></span></td>
                    <td><?= e($log['reminder_type']) ?></td>
                    <td>
                        <?php $badge = $log['status'] === 'sent' ? 'text-bg-success' : ($log['status'] === 'failed' ? 'text-bg-danger' : 'text-bg-secondary'); ?>
                        <span class="badge <?= $badge ?>"><?= e($log['status']) ?></span>
                    </td>
                    <td class="small text-muted"><?= e($log['response_text'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
