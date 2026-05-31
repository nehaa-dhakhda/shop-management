<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$pageTitle = 'Suppliers';

$action = $_POST['action'] ?? '';
if ($action === 'add') {
    $pdo->prepare("INSERT INTO suppliers (name,phone,email,address) VALUES (?,?,?,?)")
        ->execute([$_POST['name'],$_POST['phone'],$_POST['email'],$_POST['address']]);
    flash('success', 'Supplier added.');
    redirect('/shop-erp/modules/suppliers.php');
}
if ($action === 'edit') {
    $pdo->prepare("UPDATE suppliers SET name=?,phone=?,email=?,address=? WHERE id=?")
        ->execute([$_POST['name'],$_POST['phone'],$_POST['email'],$_POST['address'],$_POST['id']]);
    flash('success', 'Supplier updated.');
    redirect('/shop-erp/modules/suppliers.php');
}
if ($action === 'delete') {
    $pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$_POST['id']]);
    flash('success', 'Supplier deleted.');
    redirect('/shop-erp/modules/suppliers.php');
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h5><i class="bi bi-building me-2"></i>Suppliers</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#supModal">
        <i class="bi bi-plus-lg me-1"></i>Add Supplier
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($suppliers as $i => $s): ?>
            <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                <td><?= htmlspecialchars($s['phone']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($s['address']) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-icon me-1" onclick='editSup(<?= json_encode($s) ?>)'><i class="bi bi-pencil"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$suppliers): ?><tr><td colspan="6" class="text-center text-muted py-4">No suppliers.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="supModal" tabindex="-1">
    <div class="modal-dialog"><form method="POST" class="modal-content">
        <input type="hidden" name="action" id="sAct" value="add">
        <input type="hidden" name="id" id="sId">
        <div class="modal-header"><h5 class="modal-title" id="sTitle">Add Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" id="sName" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" id="sPhone" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="sEmail" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Address</label><textarea name="address" id="sAddr" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form></div>
</div>

<script>
function editSup(s) {
    document.getElementById('sAct').value  = 'edit';
    document.getElementById('sId').value   = s.id;
    document.getElementById('sTitle').textContent = 'Edit Supplier';
    document.getElementById('sName').value  = s.name;
    document.getElementById('sPhone').value = s.phone;
    document.getElementById('sEmail').value = s.email;
    document.getElementById('sAddr').value  = s.address;
    new bootstrap.Modal(document.getElementById('supModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
