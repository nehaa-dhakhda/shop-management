<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$pageTitle = 'Purchases';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'new_purchase') {
    $items   = json_decode($_POST['items_json'], true);
    $suppId  = $_POST['supplier_id'] ?: null;
    $paid    = floatval($_POST['paid_amount']);
    $note    = trim($_POST['note'] ?? '');

    if ($items && count($items) > 0) {
        $total = 0;
        foreach ($items as $item) $total += $item['qty'] * $item['price'];

        $ref = generateRef();
        $pdo->prepare("INSERT INTO purchases (reference_no,supplier_id,total_amount,paid_amount,note) VALUES (?,?,?,?,?)")
            ->execute([$ref, $suppId, $total, $paid, $note]);
        $poId = $pdo->lastInsertId();

        foreach ($items as $item) {
            $pdo->prepare("INSERT INTO purchase_items (purchase_id,product_id,quantity,unit_price,total) VALUES (?,?,?,?,?)")
                ->execute([$poId, $item['id'], $item['qty'], $item['price'], $item['qty']*$item['price']]);
            $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id=?")
                ->execute([$item['qty'], $item['id']]);
        }

        flash('success', "Purchase recorded! Ref: $ref");
        redirect('/shop-erp/modules/purchases.php');
    }
}

$products  = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
$purchases = $pdo->query("
    SELECT p.*, s.name AS supplier
    FROM purchases p LEFT JOIN suppliers s ON p.supplier_id=s.id
    ORDER BY p.created_at DESC LIMIT 30
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#newpo">📦 New Purchase</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pohistory">📋 Purchase History</a></li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="newpo">
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Select Products to Purchase</div>
            <div class="card-body">
                <div class="search-bar mb-3">
                    <i class="bi bi-search"></i>
                    <input type="text" id="pSearch" class="form-control" placeholder="Search product…">
                </div>
                <div style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm">
                        <thead><tr><th>Product</th><th>Buy Price</th><th>Stock</th><th>Qty</th><th></th></tr></thead>
                        <tbody id="pTable">
                        <?php foreach ($products as $p): ?>
                        <tr data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= money($p['purchase_price']) ?></td>
                            <td><?= $p['stock_qty'] ?></td>
                            <td><input type="number" min="1" value="1" class="form-control form-control-sm" style="width:70px" id="qty_<?= $p['id'] ?>"></td>
                            <td><button onclick="addPO(<?= $p['id'] ?>,<?= htmlspecialchars(json_encode($p['name'])) ?>,<?= $p['purchase_price'] ?>)" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Purchase Order</div>
            <div class="card-body">
                <div id="poItems" style="min-height:120px;margin-bottom:12px">
                    <div class="text-muted text-center py-3" id="poEmpty">No items added</div>
                </div>
                <div class="d-flex justify-content-between fw-bold mb-3">
                    <span>Total</span><span id="poTotal">₹0.00</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="new_purchase">
                    <input type="hidden" name="items_json" id="poJson">
                    <div class="mb-2">
                        <select name="supplier_id" class="form-select form-select-sm">
                            <option value="">-- No Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><input type="number" name="paid_amount" class="form-control form-control-sm" placeholder="Paid Amount ₹" step="0.01" min="0"></div>
                    <div class="mb-3"><input type="text" name="note" class="form-control form-control-sm" placeholder="Note (optional)"></div>
                    <button type="submit" class="btn btn-primary w-100" onclick="return submitPO()">
                        <i class="bi bi-save me-1"></i>Record Purchase
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<div class="tab-pane fade" id="pohistory">
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Reference</th><th>Supplier</th><th>Total</th><th>Paid</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($purchases as $p): ?>
                <tr>
                    <td><code><?= htmlspecialchars($p['reference_no']) ?></code></td>
                    <td><?= htmlspecialchars($p['supplier'] ?? '—') ?></td>
                    <td><strong><?= money($p['total_amount']) ?></strong></td>
                    <td><?= money($p['paid_amount']) ?></td>
                    <td class="text-muted"><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script>
let poCart = {};

function addPO(id, name, price) {
    const qty = parseInt(document.getElementById('qty_' + id).value) || 1;
    if (poCart[id]) poCart[id].qty += qty;
    else poCart[id] = { id, name, price, qty };
    renderPO();
}

function removePO(id) { delete poCart[id]; renderPO(); }

function renderPO() {
    const ids = Object.keys(poCart);
    let total = 0;
    if (!ids.length) {
        document.getElementById('poItems').innerHTML = '<div class="text-muted text-center py-3">No items added</div>';
        document.getElementById('poTotal').textContent = '₹0.00';
        return;
    }
    document.getElementById('poItems').innerHTML = ids.map(id => {
        const i = poCart[id];
        total += i.qty * i.price;
        return `<div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background:#f8fafc;font-size:.82rem">
            <div style="flex:1"><strong>${i.name}</strong><div class="text-muted">₹${i.price} × ${i.qty}</div></div>
            <strong>₹${(i.price*i.qty).toFixed(2)}</strong>
            <button onclick="removePO(${id})" class="btn btn-sm btn-outline-danger p-1"><i class="bi bi-x"></i></button>
        </div>`;
    }).join('');
    document.getElementById('poTotal').textContent = '₹' + total.toFixed(2);
}

function submitPO() {
    if (!Object.keys(poCart).length) { alert('No items!'); return false; }
    document.getElementById('poJson').value = JSON.stringify(Object.values(poCart));
    return true;
}

document.getElementById('pSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#pTable tr').forEach(r => {
        r.style.display = r.dataset.name?.includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
