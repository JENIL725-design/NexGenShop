<?php
include 'admin_header.php';
require 'db_connect.php';

// 1. Get Stats
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn();
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='pending'")->fetchColumn();

// 2. Get Top Selling Products
$top_products = $pdo->query("
    SELECT product_name, SUM(quantity) as total_sold 
    FROM order_items 
    GROUP BY product_id 
    ORDER BY total_sold DESC 
    LIMIT 5
")->fetchAll();
?>

<style>
    .stat-card {
        border: none;
        border-radius: 20px;
        padding: 30px;
        color: white;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s;
    }
    .stat-card:hover { transform: translateY(-5px); }
    
    .stat-card .icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 4rem;
        opacity: 0.2;
    }

    .bg-gradient-purple { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    .bg-gradient-blue { background: linear-gradient(135deg, #3b82f6, #0ea5e9); }
    .bg-gradient-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }

    .table-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        border: none;
    }
</style>

<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-1">Dashboard Overview</h2>
            <p class="text-muted">Welcome back, Admin! Here's what's happening today.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-dark border px-3 py-2 shadow-sm rounded-pill">
                <i class="fa-regular fa-calendar me-2"></i> <?php echo date('F d, Y'); ?>
            </span>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card bg-gradient-purple shadow-lg">
                <h5 class="opacity-75 mb-1">Total Revenue</h5>
                <h2 class="fw-bold mb-0">$<?php echo number_format($total_revenue, 2); ?></h2>
                <i class="fa-solid fa-wallet icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-gradient-blue shadow-lg">
                <h5 class="opacity-75 mb-1">Total Orders</h5>
                <h2 class="fw-bold mb-0"><?php echo $total_orders; ?></h2>
                <i class="fa-solid fa-bag-shopping icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-gradient-orange shadow-lg">
                <h5 class="opacity-75 mb-1">Pending Tickets</h5>
                <h2 class="fw-bold mb-0"><?php echo $pending_complaints; ?></h2>
                <i class="fa-solid fa-comments icon"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card table-card p-3">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="fw-bold"><i class="fa-solid fa-trophy text-warning me-2"></i>Top Performing Products</h5>
                </div>
                <div class="card-body">
                    <table class="table align-middle">
                        <thead class="text-secondary small text-uppercase">
                            <tr><th>Rank</th><th>Product</th><th>Sold</th><th>Growth</th></tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top_products as $p): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border rounded-circle" style="width: 25px; height: 25px; line-height: 20px;"><?php echo $rank++; ?></span></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($p['product_name']); ?></td>
                                <td><?php echo $p['total_sold']; ?> units</td>
                                <td style="width: 30%;">
                                    <div class="progress" style="height: 6px; border-radius: 10px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo min($p['total_sold'] * 5, 100); ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>