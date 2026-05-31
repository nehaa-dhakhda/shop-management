<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$pageTitle = 'Products';

// Handle actions
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO products (category_id,name,sku,description,purchase_price,sale_price,stock_qty,low_stock_alert,unit) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$_POST['category_id'],$_POST['name'],$_POST['sku'],$_POST['description'],$_POST['purchase_price'],$_POST['sale_price'],$_POST['stock_qty'],$_POST['low_stock_alert'],$_POST['unit']]);
    flash('success', 'Product added successfully.');
    redirect('/shop-erp/modules/products.php');
}

if ($action === 'edit') {
    $stmt = $pdo->prepare("UPDATE products SET category_id=?,name=?,sku=?,description=?,purchase_price=?,sale_price=?,stock_qty=?,low_stock_alert=?,unit=? WHERE id=?");
    $stmt->execute([$_POST['category_id'],$_POST['name'],$_POST['sku'],$_POST['description'],$_POST['purchase_price'],$_POST['sale_price'],$_POST['stock_qty'],$_POST['low_stock_alert'],$_POST['unit'],$_POST['id']]);
    flash('success', 'Product updated.');
    redirect('/shop-erp/modules/products.php');
}

if ($action === 'delete') {
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$_POST['id']]);
    flash('success', 'Product deleted.');
    redirect('/shop-erp/modules/products.php');
}

// Search
$search = trim($_GET['q'] ?? '');
$params = [];
$where  = 'WHERE 1=1';
if ($search) { $where .= ' AND (p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$products = $pdo->prepare("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id=c.id $where ORDER BY p.id DESC");
$products->execute($params);
$products = $products->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h5><i class="bi bi-box-seam me-2"></i>Products</h5>
    <div class="d-flex gap-2">
        <div class="search-bar">
            <i class="bi bi-search"></i>
            <form method="GET">
                <input type="text" name="q" class="form-control" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>" style="width:220px">
            </form>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#productModal">
            <i class="bi bi-plus-lg me-1"></i>Add Product
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th><th>Name</th><th>SKU</th><th>Category</th>
                    <th>Buy Price</th><th>Sale Price</th><th>Stock</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $i => $p): ?>
            <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                <td><code><?= htmlspecialchars($p['sku']) ?></code></td>
                <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                <td><?= money($p['purchase_price']) ?></td>
                <td><strong><?= money($p['sale_price']) ?></strong></td>
                <td>
                    <?php if ($p['stock_qty'] <= $p['low_stock_alert']): ?>
                        <span class="badge-status badge-danger"><?= $p['stock_qty'] ?> <?= $p['unit'] ?> ⚠️</span>
                    <?php else: ?>
                        <span class="badge-status badge-success"><?= $p['stock_qty'] ?> <?= $p['unit'] ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-icon me-1"
                        onclick='editProduct(<?= json_encode($p) ?>)' title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this product?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" id="fName" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" id="fSku" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unit</label>
                        <select name="unit" id="fUnit" class="form-select">
                            <option value="pcs">pcs</option><option value="kg">kg</option>
                            <option value="g">g</option><option value="l">l</option>
                            <option value="box">box</option><option value="pair">pair</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="fCat" class="form-select">
                            <option value="">-- None --</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Price (₹) *</label>
                        <input type="number" step="0.01" name="purchase_price" id="fBuy" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sale Price (₹) *</label>
                        <input type="number" step="0.01" name="sale_price" id="fSell" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock Qty *</label>
                        <input type="number" name="stock_qty" id="fStock" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Low Stock Alert</label>
                        <input type="number" name="low_stock_alert" id="fAlert" class="form-control" value="5">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="fDesc" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProduct(p) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = p.id;
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('fName').value  = p.name;
    document.getElementById('fSku').value   = p.sku;
    document.getElementById('fCat').value   = p.category_id;
    document.getElementById('fBuy').value   = p.purchase_price;
    document.getElementById('fSell').value  = p.sale_price;
    document.getElementById('fStock').value = p.stock_qty;
    document.getElementById('fAlert').value = p.low_stock_alert;
    document.getElementById('fUnit').value  = p.unit;
    document.getElementById('fDesc').value  = p.description;
    new bootstrap.Modal(document.getElementById('productModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
