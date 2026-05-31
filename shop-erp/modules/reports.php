<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$pageTitle = 'Reports';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// Sales summary
$summary = $pdo->prepare("
    SELECT COUNT(*) AS invoices, COALESCE(SUM(total_amount),0) AS revenue,
           COALESCE(SUM(discount),0) AS discounts, COALESCE(SUM(paid_amount),0) AS collected
    FROM sales WHERE DATE(created_at) BETWEEN ? AND ?
");
$summary->execute([$from, $to]);
$summary = $summary->fetch();

// Daily breakdown
$daily = $pdo->prepare("
    SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total_amount) AS total
    FROM sales WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at) ORDER BY day
");
$daily->execute([$from, $to]);
$daily = $daily->fetchAll();

// Top products in range
$topP = $pdo->prepare("
    SELECT p.name, SUM(si.quantity) AS qty, SUM(si.total) AS revenue
    FROM sale_items si
    JOIN products p ON si.product_id=p.id
    JOIN sales s ON si.sale_id=s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY si.product_id ORDER BY revenue DESC LIMIT 10
");
$topP->execute([$from, $to]);
$topP = $topP->fetchAll();

// Profit estimate
$profit = $pdo->prepare("
    SELECT SUM(si.quantity * (p.sale_price - p.purchase_price)) AS profit
    FROM sale_items si JOIN products p ON si.product_id=p.id
    JOIN sales s ON si.sale_id=s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
");
$profit->execute([$from, $to]);
$profit = $profit->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Date Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-auto">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="<?= $from ?>">
            </div>
            <div class="col-auto">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="<?= $to ?>">
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary">Generate</button>
                <a href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">Today</a>
                <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">This Month</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
            <div><div class="stat-label">Total Invoices</div><div class="stat-value"><?= $summary['invoices'] ?></div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
            <div><div class="stat-label">Total Revenue</div><div class="stat-value"><?= money($summary['revenue']) ?></div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-piggy-bank"></i></div>
            <div><div class="stat-label">Est. Profit</div><div class="stat-value"><?= money($profit ?? 0) ?></div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-tag"></i></div>
            <div><div class="stat-label">Discounts Given</div><div class="stat-value"><?= money($summary['discounts']) ?></div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Daily Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Daily Sales Trend</div>
            <div class="card-body">
                <canvas id="dailyChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <!-- Top Products -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Top Products (by Revenue)</div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($topP as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= $p['qty'] ?></td>
                        <td><strong><?= money($p['revenue']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$topP): ?><tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown Table -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">Daily Breakdown</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($daily as $d): ?>
                    <tr>
                        <td><?= date('d M Y, D', strtotime($d['day'])) ?></td>
                        <td><?= $d['orders'] ?></td>
                        <td><strong><?= money($d['total']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$daily): ?><tr><td colspan="3" class="text-center text-muted py-3">No sales in this range.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('d M', strtotime($d['day'])), $daily)) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode(array_column($daily, 'total')) ?>,
            fill: true,
            backgroundColor: 'rgba(37,99,235,.08)',
            borderColor: '#2563eb',
            borderWidth: 2,
            tension: .4,
            pointBackgroundColor: '#2563eb',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
