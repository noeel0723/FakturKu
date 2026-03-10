<?php
$pageTitle = 'Tax Profiles';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-percent me-2"></i>Tax Profiles</h1>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-3">Create Tax Rule</div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/ops/tax-profiles/store" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <select name="tax_type" class="form-select">
                            <option value="vat">VAT</option>
                            <option value="withholding">Withholding</option>
                            <option value="local">Local Tax</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Calculation</label>
                        <select name="calculation_method" class="form-select">
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rate (%)</label>
                        <input type="number" name="rate" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fixed Amount</label>
                        <input type="number" name="fixed_amount" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Compound</label>
                        <select name="is_compound" class="form-select">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Active</label>
                        <select name="is_active" class="form-select">
                            <option value="1" selected>Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Save Tax Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-3">Tax Preview</div>
            <div class="card-body">
                <form method="GET" action="<?= APP_URL ?>/ops/tax-profiles" class="row g-2 mb-3">
                    <div class="col-md-5">
                        <input type="number" name="preview_amount" class="form-control" step="0.01" min="0" placeholder="Base amount" value="<?= e($_GET['preview_amount'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-5">
                        <select name="profile_ids" class="form-select" required>
                            <option value="">Select profile</option>
                            <?php foreach ($profiles as $profile): ?>
                            <option value="<?= (int)$profile['id'] ?>" <?= (($_GET['profile_ids'] ?? '') == $profile['id']) ? 'selected' : '' ?>><?= e($profile['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-primary">Run</button>
                    </div>
                </form>

                <?php if ($preview): ?>
                <div class="small text-muted mb-2">Result</div>
                <div class="d-flex justify-content-between"><span>Base</span><strong><?= format_currency((float)$preview['base'], BASE_CURRENCY) ?></strong></div>
                <div class="d-flex justify-content-between"><span>Tax Total</span><strong><?= format_currency((float)$preview['tax_total'], BASE_CURRENCY) ?></strong></div>
                <div class="d-flex justify-content-between border-top pt-2 mt-2"><span>Grand Total</span><strong><?= format_currency((float)$preview['grand_total'], BASE_CURRENCY) ?></strong></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header py-3">Profile Registry</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Method</th>
                    <th>Rate</th>
                    <th>Fixed</th>
                    <th>Compound</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($profiles)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No tax profiles configured.</td></tr>
                <?php else: ?>
                <?php foreach ($profiles as $p): ?>
                <tr>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['tax_type']) ?></td>
                    <td><?= e($p['calculation_method']) ?></td>
                    <td><?= e($p['rate']) ?>%</td>
                    <td><?= $p['fixed_amount'] ? format_currency((float)$p['fixed_amount'], BASE_CURRENCY) : '-' ?></td>
                    <td><?= (int)$p['is_compound'] === 1 ? 'Yes' : 'No' ?></td>
                    <td><span class="badge <?= (int)$p['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int)$p['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
