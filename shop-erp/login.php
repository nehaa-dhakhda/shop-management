<?php
require_once __DIR__ . '/includes/config.php';

if (!empty($_SESSION['user'])) redirect('/shop-erp/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']];
        redirect('/shop-erp/index.php');
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ShopERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: #fff; border-radius: 20px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,.1); }
        .login-logo { font-size: 2.5rem; text-align: center; margin-bottom: 8px; }
        .login-title { font-weight: 800; font-size: 1.6rem; text-align: center; color: #1e293b; }
        .login-sub { color: #64748b; text-align: center; font-size: .88rem; margin-bottom: 28px; }
        .form-label { font-weight: 600; font-size: .83rem; color: #1e293b; }
        .form-control { border-radius: 10px; padding: 10px 14px; border: 1px solid #e2e8f0; font-size: .9rem; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .btn-login { background: #2563eb; color: #fff; border: none; border-radius: 10px; padding: 11px; font-weight: 700; font-size: .95rem; width: 100%; transition: background .2s; }
        .btn-login:hover { background: #1d4ed8; }
        .hint { background: #eff6ff; border-radius: 10px; padding: 10px 14px; font-size: .8rem; color: #2563eb; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">🏪</div>
        <div class="login-title">ShopERP</div>
        <div class="login-sub">Shop Management System</div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:10px;font-size:.85rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@shop.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="hint">
            <strong>Demo credentials:</strong><br>
            Email: admin@shop.com &nbsp;|&nbsp; Password: password
        </div>
    </div>
</body>
</html>
