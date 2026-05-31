<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="/shop-erp/assets/css/app.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🏪</span>
        <span class="brand-name"><?= APP_NAME ?></span>
    </div>

    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
    <div class="alert-floating alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <?php
        $nav = [
            ['icon'=>'bi-grid-fill',        'label'=>'Dashboard',  'url'=>'/shop-erp/index.php'],
            ['icon'=>'bi-box-seam',          'label'=>'Products',   'url'=>'/shop-erp/modules/products.php'],
            ['icon'=>'bi-cart-check-fill',   'label'=>'Sales / POS','url'=>'/shop-erp/modules/sales.php'],
            ['icon'=>'bi-people-fill',       'label'=>'Customers',  'url'=>'/shop-erp/modules/customers.php'],
            ['icon'=>'bi-truck',             'label'=>'Purchases',  'url'=>'/shop-erp/modules/purchases.php'],
            ['icon'=>'bi-building',          'label'=>'Suppliers',  'url'=>'/shop-erp/modules/suppliers.php'],
            ['icon'=>'bi-bar-chart-fill',    'label'=>'Reports',    'url'=>'/shop-erp/modules/reports.php'],
        ];
        $current = $_SERVER['REQUEST_URI'];
        foreach ($nav as $item):
            $active = strpos($current, $item['url']) !== false ? 'active' : '';
        ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $active ?>">
            <i class="<?= $item['icon'] ?>"></i>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
                <div class="user-role"><?= $_SESSION['user']['role'] ?></div>
            </div>
        </div>
        <a href="/shop-erp/logout.php" class="btn-logout" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</div>

<!-- Main -->
<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="document.getElementById('sidebar').classList.toggle('collapsed')">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
        <div class="topbar-date"><?= date('D, d M Y') ?></div>
    </div>
    <div class="content-area">
