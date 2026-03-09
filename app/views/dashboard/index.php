<?php $pageTitle = 'Dashboard'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="page-header">
    <h1><i class="bi bi-grid-1x2 me-2"></i>Dashboard</h1>
</div>

<div class="card mb-4" style="overflow:hidden; background:linear-gradient(120deg,#edf2f6 0%, #dce6ef 50%, #eaf0f5 100%); border-color:#d7e0e8;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1" style="font-weight:700; color:#2A384C;">Ringkasan Kinerja Penagihan</h5>
                <div class="text-muted small">Snapshot real-time dari transaksi dan arus kas invoice.</div>
            </div>
            <a href="<?= APP_URL ?>/invoices/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Create Invoice</a>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="stat-pill h-100">
                    <div class="small text-muted">Total Invoice</div>
                    <div class="fs-3 fw-bold" style="color:#2A384C;"><?= (int)($stats['total_invoices'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-pill h-100">
                    <div class="small text-muted">Invoice Lunas</div>
                    <div class="fs-3 fw-bold" style="color:#1c8f75;"><?= (int)($stats['paid_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-pill h-100">
                    <div class="small text-muted">Belum Lunas</div>
                    <div class="fs-3 fw-bold" style="color:#b98721;"><?= (int)($stats['unpaid_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-pill h-100">
                    <div class="small text-muted">Overdue</div>
                    <div class="fs-3 fw-bold" style="color:#b95858;"><?= (int)($stats['overdue_count'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="mb-0">Revenue Snapshot</h6>
                    <span class="small text-muted">Base <?= BASE_CURRENCY ?></span>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Total Revenue</div>
                    <div class="fs-5 fw-bold" style="color:#2A384C;"><?= format_currency((float)($stats['total_revenue'] ?? 0), BASE_CURRENCY) ?></div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Revenue Dibayar</div>
                    <div class="fs-6 fw-bold" style="color:#1c8f75;"><?= format_currency((float)($stats['paid_revenue'] ?? 0), BASE_CURRENCY) ?></div>
                </div>
                <div class="small text-muted mb-2">Outstanding</div>
                <div class="progress" role="progressbar" style="height:10px; background:#e6edf2;">
                    <?php
                    $totalRevenue = (float)($stats['total_revenue'] ?? 0);
                    $outstanding = (float)($stats['outstanding'] ?? 0);
                    $outstandingPercent = $totalRevenue > 0 ? min(100, round(($outstanding / $totalRevenue) * 100)) : 0;
                    ?>
                    <div class="progress-bar" style="width: <?= $outstandingPercent ?>%; background:#A0B2C2;"></div>
                </div>
                <div class="small mt-2" style="color:#6f7f8f;"><?= $outstandingPercent ?>% dari total revenue masih outstanding.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="mb-0">Kesehatan Portofolio</h6>
                    <span class="small text-muted">Invoice Mix</span>
                </div>
                <?php
                $totalInvoices = max(1, (int)($stats['total_invoices'] ?? 0));
                $paidPercent = round(((int)($stats['paid_count'] ?? 0) / $totalInvoices) * 100);
                $unpaidPercent = round(((int)($stats['unpaid_count'] ?? 0) / $totalInvoices) * 100);
                $overduePercent = round(((int)($stats['overdue_count'] ?? 0) / $totalInvoices) * 100);
                ?>
                <div class="mb-2 small text-muted">Paid <?= $paidPercent ?>%</div>
                <div class="progress mb-3" style="height:8px; background:#e8edf2;"><div class="progress-bar" style="width: <?= $paidPercent ?>%; background:#26b896;"></div></div>

                <div class="mb-2 small text-muted">Unpaid <?= $unpaidPercent ?>%</div>
                <div class="progress mb-3" style="height:8px; background:#e8edf2;"><div class="progress-bar" style="width: <?= $unpaidPercent ?>%; background:#efb341;"></div></div>

                <div class="mb-2 small text-muted">Overdue <?= $overduePercent ?>%</div>
                <div class="progress" style="height:8px; background:#e8edf2;"><div class="progress-bar" style="width: <?= $overduePercent ?>%; background:#d26b6b;"></div></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="mb-0">Resource</h6>
                    <span class="small text-muted">Master Data</span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 mb-2" style="background:#f4f7fa; border-radius:10px;">
                    <div>
                        <div class="small text-muted">Client Aktif</div>
                        <div class="fw-bold" style="font-size:22px;"><?= (int)$clientCount ?></div>
                    </div>
                    <i class="bi bi-people" style="font-size:24px; color:#7d8fa1;"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3" style="background:#f4f7fa; border-radius:10px;">
                    <div>
                        <div class="small text-muted">Produk/Jasa</div>
                        <div class="fw-bold" style="font-size:22px;"><?= (int)$productCount ?></div>
                    </div>
                    <i class="bi bi-box-seam" style="font-size:24px; color:#7d8fa1;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <span><i class="bi bi-receipt me-2"></i>Invoice Terbaru</span>
        <a href="<?= APP_URL ?>/invoices" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-right me-1"></i>Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Klien</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Jatuh Tempo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInvoices)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada invoice</td></tr>
                <?php else: ?>
                <?php foreach ($recentInvoices as $inv): ?>
                <tr style="cursor:pointer" onclick="location.href='<?= APP_URL ?>/invoices/show/<?= $inv['id'] ?>'">
                    <td class="fw-semibold"><?= e($inv['invoice_number']) ?></td>
                    <td><?= e($inv['client_name']) ?></td>
                    <td><?= format_currency((float)$inv['total'], $inv['currency']) ?></td>
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
                    <td><?= format_date($inv['due_date']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
