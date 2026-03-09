<?php $pageTitle = 'Produk & Jasa'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="page-header">
    <h1><i class="bi bi-box-seam me-2"></i>Produk & Jasa</h1>
    <a href="<?= APP_URL ?>/products/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Deskripsi</th>
                    <th>Harga</th>
                    <th>Satuan</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada produk</td></tr>
                <?php else: ?>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td class="fw-semibold"><?= e($p['name']) ?></td>
                    <td class="text-muted"><?= e($p['description'] ?? '-') ?></td>
                    <td><?= format_currency((float)$p['unit_price'], $p['currency']) ?></td>
                    <td><?= e($p['unit']) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/products/edit/<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= APP_URL ?>/products/delete/<?= $p['id'] ?>" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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
