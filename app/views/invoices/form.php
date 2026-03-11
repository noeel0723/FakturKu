<?php
$isEdit = !empty($invoice['id']);
$pageTitle = $isEdit ? 'Edit Faktur' : 'Buat Faktur Baru';
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-receipt me-2"></i><?= $pageTitle ?></h1>
    <a href="<?= APP_URL ?>/invoices" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<form method="POST" action="<?= APP_URL ?>/invoices/<?= $isEdit ? 'update/'.$invoice['id'] : 'store' ?>" id="invoiceForm">
<div class="row g-3">
    <!-- Left: Invoice details -->
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header py-3">Detail Faktur</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Klien <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-select" required>
                            <option value="">-- Pilih Klien --</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($invoice['client_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?> <?= $c['company'] ? '(' . e($c['company']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mata Uang</label>
                        <select name="currency" class="form-select" id="invoiceCurrency">
                            <?php foreach ($currencies as $cur): ?>
                            <option value="<?= e($cur['code']) ?>" <?= ($invoice['currency'] ?? BASE_CURRENCY) === $cur['code'] ? 'selected' : '' ?>>
                                <?= e($cur['code']) ?> (<?= e($cur['symbol']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <?php $statuses = ['draft'=>'Draf','sent'=>'Terkirim']; ?>
                            <?php foreach ($statuses as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($invoice['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Terbit</label>
                        <input type="date" name="issue_date" class="form-control" value="<?= e($invoice['issue_date'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-control" value="<?= e($invoice['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="card mb-3">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span>Item Faktur</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="addItem()"><i class="bi bi-plus-lg me-1"></i>Tambah Item</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width:200px">Produk</th>
                                <th>Deskripsi</th>
                                <th style="width:80px">Jml</th>
                                <th style="width:70px">Satuan</th>
                                <th style="width:130px">Harga</th>
                                <th style="width:130px">Jumlah</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php
                            $existingItems = $items ?? [];
                            if (empty($existingItems)) $existingItems = [['product_id'=>'','description'=>'','quantity'=>1,'unit'=>'pcs','unit_price'=>0]];
                            foreach ($existingItems as $idx => $it):
                            ?>
                            <tr class="item-row">
                                <td>
                                    <select name="items[<?= $idx ?>][product_id]" class="form-select form-select-sm product-select" onchange="fillProduct(this, <?= $idx ?>)">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-price="<?= $p['unit_price'] ?>" data-desc="<?= e($p['name']) ?>" data-unit="<?= e($p['unit']) ?>"
                                            <?= ($it['product_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                            <?= e($p['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="items[<?= $idx ?>][description]" class="form-control form-control-sm" value="<?= e($it['description'] ?? '') ?>" required></td>
                                <td><input type="number" name="items[<?= $idx ?>][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="<?= $it['quantity'] ?? 1 ?>" onchange="calcRow(this)"></td>
                                <td><input type="text" name="items[<?= $idx ?>][unit]" class="form-control form-control-sm" value="<?= e($it['unit'] ?? 'pcs') ?>"></td>
                                <td><input type="number" name="items[<?= $idx ?>][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="<?= $it['unit_price'] ?? 0 ?>" onchange="calcRow(this)"></td>
                                <td><span class="item-amount fw-semibold">0</span></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)"><i class="bi bi-x"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="card">
            <div class="card-body">
                <label class="form-label fw-semibold">Catatan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan untuk klien..."><?= e($invoice['notes'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Right: Summary -->
    <div class="col-md-4">
        <div class="card sticky-top" style="top:20px">
            <div class="card-header py-3">Ringkasan</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Pajak (%)</label>
                    <input type="number" name="tax_rate" class="form-control" id="taxRate" step="0.01" min="0" value="<?= $invoice['tax_rate'] ?? 11 ?>" onchange="calcTotal()">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Diskon</label>
                    <input type="number" name="discount_amount" class="form-control" id="discountAmount" step="0.01" min="0" value="<?= $invoice['discount_amount'] ?? 0 ?>" onchange="calcTotal()">
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span id="subtotalDisplay" class="fw-semibold">0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Pajak</span>
                    <span id="taxDisplay">0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Diskon</span>
                    <span id="discountDisplay">-0</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold fs-5">TOTAL</span>
                    <span id="totalDisplay" class="fw-bold fs-5 text-primary">0</span>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-check-lg me-1"></i>Simpan Faktur</button>
                <a href="<?= APP_URL ?>/invoices" class="btn btn-light w-100">Batal</a>
            </div>
        </div>
    </div>
</div>
</form>

<script>
const products = <?= json_encode(array_map(function($p){ return ['id'=>$p['id'],'name'=>$p['name'],'price'=>$p['unit_price'],'unit'=>$p['unit']]; }, $products)) ?>;
let itemIndex = <?= count($existingItems) ?>;

function addItem() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    let productOptions = '<option value="">-- Select --</option>';
    products.forEach(p => {
        productOptions += `<option value="${p.id}" data-price="${p.price}" data-desc="${p.name}" data-unit="${p.unit}">${p.name}</option>`;
    });
    tr.innerHTML = `
        <td><select name="items[${itemIndex}][product_id]" class="form-select form-select-sm product-select" onchange="fillProduct(this, ${itemIndex})">${productOptions}</select></td>
        <td><input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm" required></td>
        <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="1" onchange="calcRow(this)"></td>
        <td><input type="text" name="items[${itemIndex}][unit]" class="form-control form-control-sm" value="pcs"></td>
        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" onchange="calcRow(this)"></td>
        <td><span class="item-amount fw-semibold">0</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
    itemIndex++;
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    calcTotal();
}

function fillProduct(sel, idx) {
    const opt = sel.options[sel.selectedIndex];
    const row = sel.closest('tr');
    if (opt.value) {
        row.querySelector(`[name="items[${idx}][description]"]`).value = opt.dataset.desc || '';
        row.querySelector(`[name="items[${idx}][unit_price]"]`).value = opt.dataset.price || 0;
        row.querySelector(`[name="items[${idx}][unit]"]`).value = opt.dataset.unit || 'pcs';
    }
    calcRow(sel);
}

function calcRow(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const amount = qty * price;
    row.querySelector('.item-amount').textContent = amount.toLocaleString('en-US');
    calcTotal();
}

function calcTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        subtotal += qty * price;
    });
    const taxRate = parseFloat(document.getElementById('taxRate').value) || 0;
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax - discount;

    document.getElementById('subtotalDisplay').textContent = subtotal.toLocaleString('en-US');
    document.getElementById('taxDisplay').textContent = tax.toLocaleString('en-US');
    document.getElementById('discountDisplay').textContent = '-' + discount.toLocaleString('en-US');
    document.getElementById('totalDisplay').textContent = Math.max(0, total).toLocaleString('en-US');
}

// Init calculation
document.addEventListener('DOMContentLoaded', calcTotal);
</script>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
