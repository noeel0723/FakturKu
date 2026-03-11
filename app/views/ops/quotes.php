<?php
$pageTitle = 'Penawaran';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-file-earmark-text me-2"></i>Penawaran ke Faktur</h1>
</div>

<div class="card mb-3">
    <div class="card-header py-3">Buat Penawaran</div>
    <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/ops/quotes/store" class="row g-3">
            <div class="col-md-4">
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
                <label class="form-label">Tarif Pajak</label>
                <input type="number" name="tax_rate" class="form-control" step="0.01" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label">Diskon</label>
                <input type="number" name="discount_amount" class="form-control" step="0.01" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label">Berlaku Hingga</label>
                <input type="date" name="valid_until" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">Catatan</label>
                <input type="text" name="notes" class="form-control" placeholder="Catatan komersial">
            </div>

            <div class="col-12">
                <h6 class="mb-2">Item Baris</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="quoteItemsTable">
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
                                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuoteRow(this)"><i class="bi bi-x"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addQuoteRow()">+ Tambah Baris</button>
            </div>

            <div class="col-12">
                <button class="btn btn-primary">Buat Penawaran</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header py-3">Daftar Penawaran</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Penawaran</th>
                    <th>Klien</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Berlaku Hingga</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quotes)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada penawaran tersedia.</td></tr>
                <?php else: ?>
                <?php foreach ($quotes as $q): ?>
                <tr>
                    <td><?= e($q['quote_number']) ?></td>
                    <td><?= e($q['client_name']) ?></td>
                    <td><?= format_currency((float)$q['total'], $q['currency']) ?></td>
                    <td><span class="badge <?= $q['status'] === 'converted' ? 'text-bg-success' : 'text-bg-primary' ?>"><?= e($q['status']) ?></span></td>
                    <td><?= $q['valid_until'] ? format_date($q['valid_until']) : '-' ?></td>
                    <td>
                        <?php if ($q['status'] !== 'converted'): ?>
                        <form method="POST" action="<?= APP_URL ?>/ops/quotes/convert/<?= (int)$q['id'] ?>">
                            <button class="btn btn-sm btn-outline-primary">Ubah ke Faktur</button>
                        </form>
                        <?php else: ?>
                        <a href="<?= APP_URL ?>/invoices/show/<?= (int)$q['converted_invoice_id'] ?>" class="btn btn-sm btn-light border">Buka Faktur</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let quoteIdx = 1;
function addQuoteRow() {
    const tbody = document.querySelector('#quoteItemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input name="items[${quoteIdx}][description]" class="form-control" required></td>
        <td><input name="items[${quoteIdx}][quantity]" type="number" class="form-control" step="0.01" min="0.01" value="1"></td>
        <td><input name="items[${quoteIdx}][unit]" class="form-control" value="pcs"></td>
        <td><input name="items[${quoteIdx}][unit_price]" type="number" class="form-control" step="0.01" min="0" value="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuoteRow(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
    quoteIdx++;
}
function removeQuoteRow(btn) {
    const rows = document.querySelectorAll('#quoteItemsTable tbody tr');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
}
</script>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
