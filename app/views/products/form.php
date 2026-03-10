<?php
$isEdit = !empty($product['id']);
$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-box-seam me-2"></i><?= $pageTitle ?></h1>
    <a href="<?= APP_URL ?>/products" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/products/<?= $isEdit ? 'update/'.$product['id'] : 'store' ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($product['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control" step="0.01" min="0" value="<?= e($product['unit_price'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Currency</label>
                    <select name="currency" class="form-select">
                        <?php foreach ($currencies as $cur): ?>
                        <option value="<?= e($cur['code']) ?>" <?= ($product['currency'] ?? BASE_CURRENCY) === $cur['code'] ? 'selected' : '' ?>>
                            <?= e($cur['code']) ?> (<?= e($cur['symbol']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Unit</label>
                    <input type="text" name="unit" class="form-control" value="<?= e($product['unit'] ?? 'pcs') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= (int)($product['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= (int)($product['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= e($product['description'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                <a href="<?= APP_URL ?>/products" class="btn btn-light ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
