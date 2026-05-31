<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();
$pageTitle = 'Dashboard';

// Stats
$todaySales    = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCustomers= $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$lowStock      = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty <= low_stock_alert")->fetchColumn();

// Monthly sales chart (last 6 months)
$monthlySales = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b') AS month, COALESCE(SUM(total_amount),0) AS total
    FROM sales
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at)
")->fetchAll();

// Recent sales
$recentSales = $pdo->query("
    SELECT s.invoice_no, c.name AS customer, s.total_amount, s.payment_method, s.created_at
    FROM sales s LEFT JOIN customers c ON s.customer_id=c.id
    ORDER BY s.created_at DESC LIMIT 8
")->fetchAll();

// Top products
$topProducts = $pdo->query("
    SELECT p.name, SUM(si.quantity) AS sold
    FROM sale_items si JOIN products p ON si.product_id=p.id
    GROUP BY si.product_id ORDER BY sold DESC LIMIT 5
")->fetchAll();

// Low stock items
$lowStockItems = $pdo->query("
    SELECT name, stock_qty, low_stock_alert FROM products
    WHERE stock_qty <= low_stock_alert ORDER BY stock_qty ASC LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-currency-rupee"></i></div>
            <div>
                <div class="stat-label">Today's Sales</div>
                <div class="stat-value"><?= money($todaySales) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="stat-label">Total Products</div>
                <div class="stat-value"><?= $totalProducts ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-label">Customers</div>
                <div class="stat-value"><?= $totalCustomers ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <div class="stat-label">Low Stock Items</div>
                <div class="stat-value"><?= $lowStock ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">Sales (Last 6 Months)</div>
            <div class="card-body">
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <!-- Top Products -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Top Selling Products</div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Product</th><th>Sold</th></tr></thead>
                    <tbody>
                    <?php foreach ($topProducts as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><span class="badge-status badge-info"><?= $p['sold'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$topProducts): ?><tr><td colspan="2" class="text-center text-muted py-4">No data</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent Sales -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Recent Sales
                <a href="/shop-erp/modules/sales.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentSales as $s): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($s['invoice_no']) ?></code></td>
                        <td><?= htmlspecialchars($s['customer'] ?? 'Walk-in') ?></td>
                        <td><strong><?= money($s['total_amount']) ?></strong></td>
                        <td><span class="badge-status badge-info"><?= $s['payment_method'] ?></span></td>
                        <td class="text-muted"><?= date('d M, H:i', strtotime($s['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentSales): ?><tr><td colspan="5" class="text-center text-muted py-4">No sales yet</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Low Stock -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Low Stock Alerts</div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Product</th><th>Stock</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStockItems as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><span class="badge-status badge-danger"><?= $p['stock_qty'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStockItems): ?><tr><td colspan="2" class="text-center text-muted py-4">✅ All stocked</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const labels = <?= json_encode(array_column($monthlySales, 'month')) ?>;
const data   = <?= json_encode(array_column($monthlySales, 'total')) ?>;

new Chart(document.getElementById('salesChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Sales (₹)',
            data,
            backgroundColor: 'rgba(37,99,235,.15)',
            borderColor: '#2563eb',
            borderWidth: 2,
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Plus Jakarta Sans' } } },
            x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans' } } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
