<?php
// ── Database Configuration ──
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_erp');
define('APP_NAME', 'ShopERP');
define('CURRENCY', '₹');

// ── Connect ──
$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

// ── Session ──
session_start();

// ── Helpers ──
function money($n) {
    return CURRENCY . number_format($n, 2);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function requireLogin() {
    if (empty($_SESSION['user'])) {
        redirect('/shop-erp/login.php');
    }
}

function generateInvoice() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

function generateRef() {
    return 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}
?>
