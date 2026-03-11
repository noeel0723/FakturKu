<?php
$pageTitle = 'Profil Pajak';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-percent me-2"></i>Profil Pajak</h1>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-3">Buat Aturan Pajak</div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/ops/tax-profiles/store" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis</label>
                        <select name="tax_type" class="form-select">
                            <option value="vat">PPN</option>
                            <option value="withholding">PPh</option>
                            <option value="local">Pajak Daerah</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Metode Perhitungan</label>
                        <select name="calculation_method" class="form-select">
                            <option value="percentage">Persentase</option>
                            <option value="fixed">Jumlah Tetap</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tarif (%)</label>
                        <input type="number" name="rate" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Tetap</label>
                        <input type="number" name="fixed_amount" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Majemuk</label>
                        <select name="is_compound" class="form-select">
                            <option value="0">Tidak</option>
                            <option value="1">Ya</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Aktif</label>
                        <select name="is_active" class="form-select">
                            <option value="1" selected>Ya</option>
                            <option value="0">Tidak</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Simpan Profil Pajak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-3">Pratinjau Pajak</div>
            <div class="card-body">
                <form method="GET" action="<?= APP_URL ?>/ops/tax-profiles" class="row g-2 mb-3">
                    <div class="col-md-5">
                        <input type="number" name="preview_amount" class="form-control" step="0.01" min="0" placeholder="Jumlah dasar" value="<?= e($_GET['preview_amount'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-5">
                        <select name="profile_ids" class="form-select" required>
                            <option value="">Pilih profil</option>
                            <?php foreach ($profiles as $profile): ?>
                            <option value="<?= (int)$profile['id'] ?>" <?= (($_GET['profile_ids'] ?? '') == $profile['id']) ? 'selected' : '' ?>><?= e($profile['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-primary">Hitung</button>
                    </div>
                </form>

                <?php if ($preview): ?>
                <div class="small text-muted mb-2">Hasil</div>
                <div class="d-flex justify-content-between"><span>Dasar</span><strong><?= format_currency((float)$preview['base'], BASE_CURRENCY) ?></strong></div>
                <div class="d-flex justify-content-between"><span>Total Pajak</span><strong><?= format_currency((float)$preview['tax_total'], BASE_CURRENCY) ?></strong></div>
                <div class="d-flex justify-content-between border-top pt-2 mt-2"><span>Total Akhir</span><strong><?= format_currency((float)$preview['grand_total'], BASE_CURRENCY) ?></strong></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header py-3">Daftar Profil</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>Metode</th>
                    <th>Tarif</th>
                    <th>Tetap</th>
                    <th>Majemuk</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($profiles)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada profil pajak dikonfigurasi.</td></tr>
                <?php else: ?>
                <?php foreach ($profiles as $p): ?>
                <tr>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['tax_type']) ?></td>
                    <td><?= e($p['calculation_method']) ?></td>
                    <td><?= e($p['rate']) ?>%</td>
                    <td><?= $p['fixed_amount'] ? format_currency((float)$p['fixed_amount'], BASE_CURRENCY) : '-' ?></td>
                    <td><?= (int)$p['is_compound'] === 1 ? 'Ya' : 'Tidak' ?></td>
                    <td><span class="badge <?= (int)$p['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int)$p['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
