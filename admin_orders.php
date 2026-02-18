<?php
include 'admin_header.php';
require 'db_connect.php';

// HANDLE STATUS UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        echo "<script>Swal.fire('Updated!', 'Order marked as " . ucfirst(str_replace('_', ' ', $new_status)) . "', 'success');</script>";
    } catch (PDOException $e) {
        echo "<script>Swal.fire('Error', 'Database update failed.', 'error');</script>";
    }
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
?>

<style>
    /* Modern Table Styling */
    .table-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    
    .table thead th {
        background-color: #f8fafc;
        border-bottom: 2px solid #edf2f7;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 18px;
        letter-spacing: 0.5px;
    }
    
    .table tbody td {
        padding: 20px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.95rem;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* ✨ ACTION BUTTONS REDESIGN ✨ */
    .btn-action {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        color: white;
        margin: 0 3px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .btn-action:hover {
        transform: translateY(-4px) scale(1.1);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }
    
    .btn-action i { font-size: 0.9rem; }

    /* Action Colors */
    .btn-dispatch { background: linear-gradient(135deg, #0dcaf0, #0aa2c0); }
    .btn-transit { background: linear-gradient(135deg, #6366f1, #4f46e5); }
    .btn-out { background: linear-gradient(135deg, #ffc107, #ffca2c); color: #333; }
    .btn-deliver { background: linear-gradient(135deg, #198754, #157347); }
    .btn-cancel { background: linear-gradient(135deg, #dc3545, #b02a37); }
    
    /* Status Badge Soft Style */
    .badge-soft {
        padding: 6px 12px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
    }
    .bg-soft-warning { background-color: #fff8e1; color: #d97706; }
    .bg-soft-info { background-color: #e0f2fe; color: #0284c7; }
    .bg-soft-primary { background-color: #eef2ff; color: #6366f1; }
    .bg-soft-success { background-color: #dcfce7; color: #16a34a; }
    .bg-soft-danger { background-color: #fee2e2; color: #dc2626; }

</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark m-0">Order Management</h3>
        <span class="badge bg-white text-secondary border shadow-sm rounded-pill px-3 py-2">
            Total Orders: <?php echo count($orders); ?>
        </span>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Current Status</th>
                            <th class="text-center">Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                <div class="small text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div class="small text-muted">
                                    <i class="fa-regular fa-envelope me-1"></i> <?php echo htmlspecialchars($order['customer_email']); ?>
                                </div>
                            </td>
                            <td class="fw-bold text-dark">$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <?php 
                                    $s = $order['status'];
                                    $badgeClass = 'bg-soft-secondary';
                                    if($s=='pending') $badgeClass='bg-soft-warning';
                                    if($s=='dispatched') $badgeClass='bg-soft-info';
                                    if($s=='in_transit') $badgeClass='bg-soft-primary';
                                    if($s=='out_for_delivery') $badgeClass='bg-soft-primary';
                                    if($s=='delivered') $badgeClass='bg-soft-success';
                                    if($s=='cancelled') $badgeClass='bg-soft-danger';
                                ?>
                                <span class="badge badge-soft <?php echo $badgeClass; ?>">
                                    <?php echo str_replace('_', ' ', $s); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <form method="POST" class="d-flex justify-content-center">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    
                                    <button type="submit" name="status" value="dispatched" class="btn-action btn-dispatch" title="Mark Dispatched" data-bs-toggle="tooltip">
                                        <i class="fa-solid fa-box-open"></i>
                                    </button>
                                    
                                    <button type="submit" name="status" value="in_transit" class="btn-action btn-transit" title="In Transit" data-bs-toggle="tooltip">
                                        <i class="fa-solid fa-plane"></i>
                                    </button>

                                    <button type="submit" name="status" value="out_for_delivery" class="btn-action btn-out" title="Out for Delivery" data-bs-toggle="tooltip">
                                        <i class="fa-solid fa-truck-fast"></i>
                                    </button>
                                    
                                    <button type="submit" name="status" value="delivered" class="btn-action btn-deliver" title="Mark Delivered" data-bs-toggle="tooltip">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    
                                    <button type="submit" name="status" value="cancelled" class="btn-action btn-cancel" title="Cancel Order" onclick="return confirm('Are you sure you want to cancel this order?')" data-bs-toggle="tooltip">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Bootstrap Tooltips for the buttons
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

</body>
</html>