<?php
$isEdit = !empty($client['id']);
$pageTitle = $isEdit ? 'Edit Klien' : 'Tambah Klien';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person me-2"></i><?= $pageTitle ?></h1>
    <a href="<?= APP_URL ?>/clients" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/clients/<?= $isEdit ? 'update/'.$client['id'] : 'store' ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($client['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($client['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Perusahaan</label>
                    <input type="text" name="company" class="form-control" value="<?= e($client['company'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Telepon</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($client['phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Alamat</label>
                    <textarea name="address" class="form-control" rows="3"><?= e($client['address'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
                <a href="<?= APP_URL ?>/clients" class="btn btn-light ms-2">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
