<?php $pageTitle = 'Klien'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="page-header">
    <h1><i class="bi bi-people me-2"></i>Klien</h1>
    <a href="<?= APP_URL ?>/clients/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Klien</a>
</div>

<div class="card">
    <div class="card-header py-3">
        <form method="GET" action="<?= APP_URL ?>/clients" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:300px" placeholder="Cari klien..." value="<?= e($search ?? '') ?>">
            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Perusahaan</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada klien</td></tr>
                <?php else: ?>
                <?php foreach ($clients as $c): ?>
                <tr>
                    <td class="fw-semibold"><?= e($c['name']) ?></td>
                    <td><?= e($c['company'] ?? '-') ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['phone'] ?? '-') ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/clients/edit/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= APP_URL ?>/clients/delete/<?= $c['id'] ?>" class="d-inline" onsubmit="return confirm('Hapus klien ini?')">
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
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
