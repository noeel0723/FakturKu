<?php $pageTitle = 'Produk'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="page-header">
    <h1><i class="bi bi-box-seam me-2"></i>Produk & Layanan</h1>
    <a href="<?= APP_URL ?>/products/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</a>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="<?= APP_URL ?>/products" class="row g-2 align-items-center">
            <div class="col-lg-4 col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Cari nama/deskripsi produk" value="<?= e($search ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <select class="form-select" name="currency">
                    <option value="">Semua Mata Uang</option>
                    <?php foreach (($currencies ?? []) as $cur): ?>
                    <option value="<?= e($cur['code']) ?>" <?= ($currencyFilter ?? '') === $cur['code'] ? 'selected' : '' ?>><?= e($cur['code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= ($statusFilter ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <select class="form-select" name="sort">
                    <option value="name_asc" <?= ($sortFilter ?? '') === 'name_asc' ? 'selected' : '' ?>>Urut: Nama A-Z</option>
                    <option value="name_desc" <?= ($sortFilter ?? '') === 'name_desc' ? 'selected' : '' ?>>Urut: Nama Z-A</option>
                    <option value="price_asc" <?= ($sortFilter ?? '') === 'price_asc' ? 'selected' : '' ?>>Urut: Harga Rendah-Tinggi</option>
                    <option value="price_desc" <?= ($sortFilter ?? '') === 'price_desc' ? 'selected' : '' ?>>Urut: Harga Tinggi-Rendah</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-4 d-flex gap-2 justify-content-md-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/products">Setel Ulang</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Produk</th>
                    <th>Deskripsi</th>
                    <th class="text-end">Harga Satuan</th>
                    <th>Mata Uang</th>
                    <th>Satuan</th>
                    <th>Status</th>
                    <th style="width:130px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">Produk tidak ditemukan.</td></tr>
                <?php else: ?>
                <?php foreach ($products as $idx => $p): ?>
                <tr>
                    <td>
                        <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(120deg,#d1d9df,#a0b2c2);display:flex;align-items:center;justify-content:center;color:#2A384C;font-weight:700;">
                            <?= $idx + 1 ?>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($p['name']) ?></div>
                        <div class="small text-muted">SKU-<?= str_pad((string)$p['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </td>
                    <td class="text-muted"><?= e($p['description'] ?: '-') ?></td>
                    <td class="text-end fw-semibold"><?= format_currency((float)$p['unit_price'], $p['currency']) ?></td>
                    <td><span class="badge text-bg-light border"><?= e($p['currency']) ?></span></td>
                    <td><?= e($p['unit']) ?></td>
                    <td>
                        <?php if ((int)$p['is_active'] === 1): ?>
                        <span class="badge text-bg-success">Aktif</span>
                        <?php else: ?>
                        <span class="badge text-bg-secondary">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/products/edit/<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= APP_URL ?>/products/delete/<?= $p['id'] ?>" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
