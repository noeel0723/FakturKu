<?php
$pageTitle = 'Penagihan Berulang';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-arrow-repeat me-2"></i>Penagihan Berulang</h1>
    <form method="POST" action="<?= APP_URL ?>/ops/recurring/run">
        <button class="btn btn-primary"><i class="bi bi-play-circle me-1"></i>Generate Faktur Jatuh Tempo</button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-header py-3">Buat Template</div>
    <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/ops/recurring/store" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Nama Template</label>
                <input type="text" name="template_name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Klien</label>
                <select name="client_id" class="form-select" required>
                    <option value="">Pilih klien</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Mata Uang</label>
                <select name="currency" class="form-select">
                    <?php foreach ($currencies as $cur): ?>
                    <option value="<?= e($cur['code']) ?>" <?= $cur['code'] === BASE_CURRENCY ? 'selected' : '' ?>><?= e($cur['code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Frekuensi</label>
                <select name="frequency" class="form-select">
                    <option value="weekly">Mingguan</option>
                    <option value="monthly" selected>Bulanan</option>
                    <option value="quarterly">Triwulanan</option>
                    <option value="yearly">Tahunan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Terbit Berikutnya</label>
                <input type="date" name="next_issue_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tarif Pajak (%)</label>
                <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Diskon</label>
                <input type="number" name="discount_amount" class="form-control" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" selected>Aktif</option>
                    <option value="paused">Dijeda</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Catatan</label>
                <input type="text" name="notes" class="form-control" placeholder="Catatan internal">
            </div>

            <div class="col-12">
                <h6 class="mb-2">Item Baris</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="recurringItemsTable">
                        <thead>
                            <tr>
                                <th>Deskripsi</th>
                                <th style="width:120px">Jml</th>
                                <th style="width:130px">Satuan</th>
                                <th style="width:160px">Harga Satuan</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input name="items[0][description]" class="form-control" required></td>
                                <td><input name="items[0][quantity]" type="number" class="form-control" step="0.01" min="0.01" value="1"></td>
                                <td><input name="items[0][unit]" class="form-control" value="pcs"></td>
                                <td><input name="items[0][unit_price]" type="number" class="form-control" step="0.01" min="0" value="0"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRecurringRow()">+ Tambah Baris</button>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Simpan Template</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header py-3">Daftar Template</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Klien</th>
                    <th>Frekuensi</th>
                    <th>Terbit Berikutnya</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada template berulang ditemukan.</td></tr>
                <?php else: ?>
                <?php foreach ($templates as $t): ?>
                <tr>
                    <td><?= e($t['template_name']) ?></td>
                    <td><?= e($t['client_name']) ?></td>
                    <td><?= ucfirst(e($t['frequency'])) ?></td>
                    <td><?= format_date($t['next_issue_date']) ?></td>
                    <td><span class="badge <?= $t['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($t['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let recurringIdx = 1;
function addRecurringRow() {
    const tbody = document.querySelector('#recurringItemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input name="items[${recurringIdx}][description]" class="form-control" required></td>
        <td><input name="items[${recurringIdx}][quantity]" type="number" class="form-control" step="0.01" min="0.01" value="1"></td>
        <td><input name="items[${recurringIdx}][unit]" class="form-control" value="pcs"></td>
        <td><input name="items[${recurringIdx}][unit_price]" type="number" class="form-control" step="0.01" min="0" value="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
    recurringIdx++;
}
function removeRow(btn) {
    const rows = document.querySelectorAll('#recurringItemsTable tbody tr');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
}
</script>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
