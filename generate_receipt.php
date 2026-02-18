<?php
session_start();
require 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || !isset($_GET['order'])) {
    header("Location: login.php");
    exit();
}

$order_num = $_GET['order'];
$email = $_SESSION['user_email'];

// 1. FETCH ORDER DATA
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND customer_email = ?");
$stmt->execute([$order_num, $email]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found or access denied.");
}

// 2. FETCH ITEMS
$stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->execute([$order['id']]);
$items = $stmt_items->fetchAll();

// 3. LOGIC: Determine Payment Status Badge
$payment_status = "PENDING PAYMENT";
$badge_color = "text-danger border-danger bg-danger bg-opacity-10"; // Default Red

// If Paid by Card OR if COD is Delivered -> Mark as PAID
if ($order['payment_method'] == 'credit_card' || $order['status'] == 'delivered') {
    $payment_status = "PAID";
    $badge_color = "text-success border-success bg-success bg-opacity-10"; // Green
}

$gst_number = "24ABCDE1234F1Z5"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $order_num; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #525659; padding: 40px 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        
        .invoice-card { 
            background: white; 
            max-width: 800px; 
            margin: auto; 
            padding: 50px; 
            box-shadow: 0 15px 30px rgba(0,0,0,0.3); 
            min-height: 1000px; 
        }

        .logo-area { color: #6366f1; font-weight: 800; font-size: 2rem; letter-spacing: -1px; }
        
        /* Dynamic Status Badge */
        .status-badge { 
            padding: 5px 15px; 
            border-radius: 4px; 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.8rem;
            display: inline-block;
            border: 1px solid;
        }

        .table thead th { border-bottom: 2px solid #eee; color: #888; text-transform: uppercase; font-size: 0.75rem; font-weight: 700; padding-bottom: 15px; }
        .table tbody td { padding: 18px 0; border-bottom: 1px solid #f8f9fa; vertical-align: middle; color: #333; }
        .total-section { background: #f8fafc; padding: 25px; border-radius: 12px; }
        
        @media print { 
            body { background: white; padding: 0; } 
            .invoice-card { box-shadow: none; max-width: 100%; padding: 0; } 
            .no-print { display: none !important; }
            .total-section { background: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>

<div class="text-center no-print mb-4">
    <button onclick="window.print()" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm me-2">
        <i class="fa-solid fa-print"></i> Print Invoice
    </button>
    <a href="my_orders.php" class="btn btn-outline-light rounded-pill px-4">Close</a>
</div>

<div class="invoice-card">
    <div class="d-flex justify-content-between align-items-start mb-5">
        <div>
            <div class="logo-area"><i class="fa-solid fa-layer-group me-2"></i>NexGenStore.</div>
            <div class="text-secondary mt-3 small" style="line-height: 1.6;">
                <strong>NexGen Retail Pvt Ltd.</strong><br>
                123 Tech Park, Cyber City<br>
                Gujarat, India 360005<br>
                GSTIN: <?php echo $gst_number; ?>
            </div>
        </div>
        <div class="text-end">
            <h2 class="fw-bold mb-1 text-dark">INVOICE</h2>
            <p class="text-muted mb-3">#<?php echo $order_num; ?></p>
            
            <div class="status-badge <?php echo $badge_color; ?>">
                <?php echo $payment_status; ?>
            </div>
        </div>
    </div>

    <hr class="my-5 opacity-10">

    <div class="row mb-5">
        <div class="col-6">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Billed To</h6>
            <h5 class="fw-bold mb-2 text-dark"><?php echo htmlspecialchars($order['customer_name']); ?></h5>
            <p class="text-secondary small mb-2" style="max-width: 250px;">
                <?php echo htmlspecialchars($order['customer_address']); ?>
            </p>
            <p class="text-secondary small">
                <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                <?php echo htmlspecialchars($order['customer_email']); ?>
            </p>
        </div>
        <div class="col-6 text-end">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Invoice Details</h6>
            <ul class="list-unstyled text-secondary small" style="line-height: 2;">
                <li><strong>Invoice Date:</strong> <?php echo date('M d, Y', strtotime($order['created_at'])); ?></li>
                <li><strong>Order ID:</strong> <?php echo $order_num; ?></li>
                <li>
                    <strong>Payment Method:</strong> 
                    <?php 
                        $pm = $order['payment_method'];
                        if($pm == 'credit_card') echo '<i class="fa-brands fa-cc-visa me-1"></i> Card';
                        else echo '<i class="fa-solid fa-money-bill-wave me-1"></i> Cash';
                    ?>
                </li>
            </ul>
        </div>
    </div>

    <table class="table table-borderless mb-5">
        <thead>
            <tr>
                <th style="width: 50%;">Item Description</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            foreach ($items as $item): 
                $line_total = $item['price'] * $item['quantity'];
                $subtotal += $line_total;
            ?>
                <tr>
                    <td>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($item['product_name']); ?></span>
                        <div class="small text-muted">Electronics / Gaming Gear</div>
                    </td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-end fw-bold">$<?php echo number_format($line_total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php 
        $gst_rate = 0.18; // 18%
        $gst_amount = $subtotal * $gst_rate;
        $grand_total = $subtotal + $gst_amount;
    ?>

    <div class="row justify-content-end">
        <div class="col-md-5">
            <div class="total-section">
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Subtotal (Excl. Tax)</span>
                    <span class="fw-bold">$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">CGST (9%)</span>
                    <span class="fw-bold">$<?php echo number_format($gst_amount / 2, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">SGST (9%)</span>
                    <span class="fw-bold">$<?php echo number_format($gst_amount / 2, 2); ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-3 small">
                    <span class="text-muted">Shipping</span>
                    <span class="text-success fw-bold">FREE</span>
                </div>
                <hr class="my-3 opacity-25">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="h5 fw-bold mb-0 text-dark">Grand Total</span>
                    <span class="h4 fw-bold text-primary mb-0">$<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 text-muted small">
        <p class="mb-1 fw-bold text-dark">Thank you for your business!</p>
        <p>If you have any questions about this invoice, please contact support@nexgenstore.com</p>
        <p class="mt-4 text-uppercase fw-bold opacity-25 small">Generated by NexGen System</p>
    </div>
    </div>
</div>

</body>
</html>