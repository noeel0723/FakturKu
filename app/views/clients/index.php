<?php $pageTitle = 'Clients'; require BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="page-header">
    <h1><i class="bi bi-buildings me-2"></i>Companies</h1>
    <a href="<?= APP_URL ?>/clients/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add New Client</a>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <form method="GET" action="<?= APP_URL ?>/clients" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search company, contact or email" value="<?= e($search ?? '') ?>">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                    <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/clients">Reset</a>
                </form>
            </div>
            <div class="col-md-7 text-md-end">
                <span class="badge text-bg-light border me-1">Total Companies: <?= count($clients) ?></span>
                <span class="badge text-bg-light border">Workspace: FakturKu</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:90px">Logo</th>
                    <th>Contact</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th style="width:120px">Open Invoices</th>
                    <th style="width:130px">Controls</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No clients found.</td></tr>
                <?php else: ?>
                <?php foreach ($clients as $index => $c): ?>
                <?php
                    $colors = ['#f5d7c7', '#d6e7fb', '#d9f4df', '#f8dbe6', '#ebe4ff'];
                    $avatarColor = $colors[$index % count($colors)];
                    $initials = strtoupper(substr($c['name'], 0, 1));
                ?>
                <tr>
                    <td>
                        <div style="width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?= $avatarColor ?>;color:#2A384C;font-weight:700;">
                            <?= e($initials) ?>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($c['name']) ?></div>
                        <div class="small text-muted">Account Contact</div>
                    </td>
                    <td><?= e($c['company'] ?: '-') ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['phone'] ?: '-') ?></td>
                    <td>
                        <span class="badge text-bg-light border">N/A</span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/clients/edit/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= APP_URL ?>/clients/delete/<?= $c['id'] ?>" class="d-inline" onsubmit="return confirm('Delete this client?')">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
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
