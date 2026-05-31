<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$pageTitle = 'Sales / POS';

// Handle sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'new_sale') {
    $items    = json_decode($_POST['cart_json'], true);
    $custId   = $_POST['customer_id'] ?: null;
    $discount = floatval($_POST['discount']);
    $paid     = floatval($_POST['paid_amount']);
    $method   = $_POST['payment_method'];
    $note     = trim($_POST['note'] ?? '');

    if ($items && count($items) > 0) {
        $total = 0;
        foreach ($items as $item) $total += $item['qty'] * $item['price'];
        $total -= $discount;

        $inv = generateInvoice();
        $stmt = $pdo->prepare("INSERT INTO sales (invoice_no,customer_id,total_amount,discount,paid_amount,payment_method,note) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$inv, $custId, $total, $discount, $paid, $method, $note]);
        $saleId = $pdo->lastInsertId();

        foreach ($items as $item) {
            $pdo->prepare("INSERT INTO sale_items (sale_id,product_id,quantity,unit_price,total) VALUES (?,?,?,?,?)")
                ->execute([$saleId, $item['id'], $item['qty'], $item['price'], $item['qty']*$item['price']]);
            $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id=?")
                ->execute([$item['qty'], $item['id']]);
        }

        flash('success', "Sale recorded! Invoice: $inv");
        redirect('/shop-erp/modules/sales.php');
    } else {
        flash('danger', 'Cart is empty.');
        redirect('/shop-erp/modules/sales.php');
    }
}

$products  = $pdo->query("SELECT * FROM products WHERE stock_qty > 0 ORDER BY name")->fetchAll();
$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();

// Sales list
$salesList = $pdo->query("
    SELECT s.*, c.name AS customer
    FROM sales s LEFT JOIN customers c ON s.customer_id=c.id
    ORDER BY s.created_at DESC LIMIT 30
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="salesTab">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#pos">🛒 New Sale (POS)</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#history">📋 Sales History</a></li>
</ul>

<div class="tab-content">
<!-- ── POS Tab ── -->
<div class="tab-pane fade show active" id="pos">
<div class="pos-grid">
    <!-- Products -->
    <div>
        <div class="mb-3 search-bar">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="prodSearch" placeholder="Search products…">
        </div>
        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $p): ?>
            <div class="product-tile" onclick="addToCart(<?= $p['id'] ?>, <?= htmlspecialchars(json_encode($p['name'])) ?>, <?= $p['sale_price'] ?>, <?= $p['stock_qty'] ?>)"
                 data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                <div style="font-size:1.8rem">📦</div>
                <div class="pt-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="pt-price"><?= money($p['sale_price']) ?></div>
                <div class="pt-stock">Stock: <?= $p['stock_qty'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart -->
    <div class="cart-panel">
        <div class="card-header" style="font-weight:700;padding:14px 16px;">
            🛒 Cart <span id="cartCount" class="badge bg-primary ms-2">0</span>
        </div>
        <div class="cart-items" id="cartItems">
            <div class="text-muted text-center py-4" id="emptyCart">Cart is empty</div>
        </div>
        <div class="cart-footer">
            <div class="d-flex justify-content-between mb-1" style="font-size:.85rem">
                <span class="text-muted">Subtotal</span>
                <strong id="subtotal">₹0.00</strong>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <label style="font-size:.82rem;white-space:nowrap">Discount (₹)</label>
                <input type="number" id="discount" class="form-control form-control-sm" value="0" min="0" oninput="calcTotal()">
            </div>
            <div class="d-flex justify-content-between mb-3" style="font-size:1rem;font-weight:700">
                <span>Total</span><span id="totalAmt">₹0.00</span>
            </div>

            <form method="POST" id="saleForm">
                <input type="hidden" name="action" value="new_sale">
                <input type="hidden" name="cart_json" id="cartJson">

                <select name="customer_id" class="form-select form-select-sm mb-2">
                    <option value="">Walk-in Customer</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="row g-2 mb-2">
                    <div class="col">
                        <input type="number" name="paid_amount" id="paidAmt" class="form-control form-control-sm" placeholder="Paid ₹" min="0" step="0.01">
                    </div>
                    <div class="col">
                        <select name="payment_method" class="form-select form-select-sm">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="discount" id="discountHidden">
                <input type="text" name="note" class="form-control form-control-sm mb-3" placeholder="Note (optional)">

                <button type="submit" class="btn btn-primary w-100" onclick="return submitSale()">
                    <i class="bi bi-check-circle me-1"></i> Complete Sale
                </button>
            </form>
        </div>
    </div>
</div>
</div>

<!-- ── History Tab ── -->
<div class="tab-pane fade" id="history">
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Discount</th><th>Paid</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($salesList as $s): ?>
                <tr>
                    <td><code><?= htmlspecialchars($s['invoice_no']) ?></code></td>
                    <td><?= htmlspecialchars($s['customer'] ?? 'Walk-in') ?></td>
                    <td><strong><?= money($s['total_amount']) ?></strong></td>
                    <td><?= money($s['discount']) ?></td>
                    <td><?= money($s['paid_amount']) ?></td>
                    <td><span class="badge-status badge-info"><?= $s['payment_method'] ?></span></td>
                    <td class="text-muted"><?= date('d M Y, H:i', strtotime($s['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$salesList): ?><tr><td colspan="7" class="text-center text-muted py-4">No sales yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script>
let cart = {};

function addToCart(id, name, price, stock) {
    if (cart[id]) {
        if (cart[id].qty >= stock) { alert('Not enough stock!'); return; }
        cart[id].qty++;
    } else {
        cart[id] = { id, name, price, qty: 1, stock };
    }
    renderCart();
}

function removeFromCart(id) { delete cart[id]; renderCart(); }

function changeQty(id, v) {
    cart[id].qty = parseInt(v);
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

function renderCart() {
    const box = document.getElementById('cartItems');
    const ids = Object.keys(cart);
    document.getElementById('cartCount').textContent = ids.length;

    if (!ids.length) { box.innerHTML = '<div class="text-muted text-center py-4">Cart is empty</div>'; calcTotal(); return; }

    box.innerHTML = ids.map(id => {
        const i = cart[id];
        return `<div class="cart-item">
            <div style="flex:1">
                <div style="font-weight:600;font-size:.82rem">${i.name}</div>
                <div style="font-size:.75rem;color:#64748b">₹${i.price} × ${i.qty}</div>
            </div>
            <input type="number" value="${i.qty}" min="1" max="${i.stock}" onchange="changeQty(${id},this.value)"
                class="form-control form-control-sm" style="width:56px">
            <button onclick="removeFromCart(${id})" class="btn btn-icon btn-outline-danger" style="width:28px;height:28px">
                <i class="bi bi-x"></i>
            </button>
        </div>`;
    }).join('');
    calcTotal();
}

function calcTotal() {
    let sub = 0;
    Object.values(cart).forEach(i => sub += i.price * i.qty);
    const disc = parseFloat(document.getElementById('discount').value) || 0;
    const total = Math.max(0, sub - disc);
    document.getElementById('subtotal').textContent = '₹' + sub.toFixed(2);
    document.getElementById('totalAmt').textContent  = '₹' + total.toFixed(2);
    document.getElementById('paidAmt').placeholder  = 'Paid ₹ (min ' + total.toFixed(2) + ')';
}

function submitSale() {
    if (!Object.keys(cart).length) { alert('Cart is empty!'); return false; }
    const disc = parseFloat(document.getElementById('discount').value) || 0;
    document.getElementById('cartJson').value = JSON.stringify(Object.values(cart));
    document.getElementById('discountHidden').value = disc;
    return true;
}

// Product search filter
document.getElementById('prodSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.product-tile').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
