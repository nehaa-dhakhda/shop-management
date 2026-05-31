<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$pageTitle = 'Customers';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $pdo->prepare("INSERT INTO customers (name,phone,email,address) VALUES (?,?,?,?)")
        ->execute([$_POST['name'],$_POST['phone'],$_POST['email'],$_POST['address']]);
    flash('success', 'Customer added.');
    redirect('/shop-erp/modules/customers.php');
}
if ($action === 'edit') {
    $pdo->prepare("UPDATE customers SET name=?,phone=?,email=?,address=? WHERE id=?")
        ->execute([$_POST['name'],$_POST['phone'],$_POST['email'],$_POST['address'],$_POST['id']]);
    flash('success', 'Customer updated.');
    redirect('/shop-erp/modules/customers.php');
}
if ($action === 'delete') {
    $pdo->prepare("DELETE FROM customers WHERE id=?")->execute([$_POST['id']]);
    flash('success', 'Customer deleted.');
    redirect('/shop-erp/modules/customers.php');
}

$search = trim($_GET['q'] ?? '');
$params = [];
$where  = '';
if ($search) { $where = 'WHERE name LIKE ? OR phone LIKE ?'; $params[] = "%$search%"; $params[] = "%$search%"; }

$customers = $pdo->prepare("SELECT * FROM customers $where ORDER BY id DESC");
$customers->execute($params);
$customers = $customers->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h5><i class="bi bi-people-fill me-2"></i>Customers</h5>
    <div class="d-flex gap-2">
        <div class="search-bar">
            <i class="bi bi-search"></i>
            <form method="GET">
                <input type="text" name="q" class="form-control" placeholder="Search…" value="<?= htmlspecialchars($search) ?>" style="width:200px">
            </form>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#custModal">
            <i class="bi bi-plus-lg me-1"></i>Add Customer
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Since</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($customers as $i => $c): ?>
            <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td><?= htmlspecialchars($c['phone']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($c['address']) ?></td>
                <td class="text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-icon me-1" onclick='editCustomer(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$customers): ?><tr><td colspan="7" class="text-center text-muted py-4">No customers found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="custModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" id="cAction" value="add">
            <input type="hidden" name="id" id="cId">
            <div class="modal-header"><h5 class="modal-title" id="cTitle">Add Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" id="cName" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" id="cPhone" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="cEmail" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Address</label><textarea name="address" id="cAddr" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCustomer(c) {
    document.getElementById('cAction').value = 'edit';
    document.getElementById('cId').value    = c.id;
    document.getElementById('cTitle').textContent = 'Edit Customer';
    document.getElementById('cName').value  = c.name;
    document.getElementById('cPhone').value = c.phone;
    document.getElementById('cEmail').value = c.email;
    document.getElementById('cAddr').value  = c.address;
    new bootstrap.Modal(document.getElementById('custModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
