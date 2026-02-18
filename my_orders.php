<?php
session_start();
include 'header.php';
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

$email = $_SESSION['user_email'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY id DESC");
$stmt->execute([$email]);
$orders = $stmt->fetchAll();

// HELPER: Convert status to a number (0 to 4) for logic
function getStatusIndex($status) {
    $stages = ['pending', 'dispatched', 'in_transit', 'out_for_delivery', 'delivered'];
    return array_search($status, $stages); // Returns 0, 1, 2, 3, or 4
}
?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<style>
    /* New Stepper Design */
    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        position: relative;
    }

    .stepper-item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
    }

    /* GRAY BACKGROUND LINE (Static) */
    .stepper-item::before {
        content: '';
        position: absolute;
        top: 20px;
        left: -50%;
        width: 100%;
        height: 3px;
        background: #e2e8f0;
        z-index: -1;
    }

    /* PURPLE FILL LINE (Animated) */
    .stepper-item::after {
        content: '';
        position: absolute;
        top: 20px;
        left: -50%;
        width: 0%; /* Start empty */
        height: 3px;
        background: #6366f1;
        z-index: -1;
        transition: width 0.5s ease-out;
    }

    /* Hide lines for the first item */
    .stepper-item:first-child::before,
    .stepper-item:first-child::after {
        content: none;
    }

    /* The Circle Icon */
    .step-counter {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 6px;
        z-index: 10; /* High z-index to stay above lines */
        position: relative;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    /* REVERSE STACKING ORDER (Fixes Line Overlap) */
    .stepper-item:nth-child(1) { z-index: 5; }
    .stepper-item:nth-child(2) { z-index: 4; }
    .stepper-item:nth-child(3) { z-index: 3; }
    .stepper-item:nth-child(4) { z-index: 2; }
    .stepper-item:nth-child(5) { z-index: 1; }

    /* --- ANIMATION KEYFRAMES --- */
    @keyframes fillLine { from { width: 0; } to { width: 100%; } }
    @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 70% { transform: scale(1.2); } 100% { transform: scale(1); opacity: 1; } }

    /* --- STATES --- */
    .stepper-item.completed::after, 
    .stepper-item.active::after {
        animation: fillLine 0.8s ease-out forwards; 
    }

    /* Delays for the line animation */
    .stepper-item:nth-child(2)::after { animation-delay: 0.2s; }
    .stepper-item:nth-child(3)::after { animation-delay: 1.0s; }
    .stepper-item:nth-child(4)::after { animation-delay: 1.8s; }
    .stepper-item:nth-child(5)::after { animation-delay: 2.6s; }

    /* Icon Styling */
    .stepper-item.completed .step-counter {
        background-color: #6366f1; border-color: #6366f1; color: white; animation: popIn 0.5s ease-out forwards;
    }
    .stepper-item.active .step-counter {
        background-color: white; border-color: #6366f1; color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
    }
    
    /* Text */
    .step-name {
        font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; margin-top: 5px;
    }
    .stepper-item.active .step-name { color: #0f172a; font-weight: 800; }
    .stepper-item.completed .step-name { color: #6366f1; }

    /* Delivery Location Styling */
    .delivery-info {
        border-left: 3px solid #6366f1; padding-left: 15px; margin-top: 20px;
    }
</style>

<div class="container py-5">
    <h2 class="fw-bold mb-4"><i class="fa-solid fa-box-open me-2 text-primary"></i>My Order History</h2>

    <?php if (count($orders) > 0): ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): 
                $current_index = getStatusIndex($order['status']);
                
                $steps = [
                    ['label' => 'Ordered', 'icon' => 'fa-clipboard-list'],
                    ['label' => 'Packed', 'icon' => 'fa-box-open'], 
                    ['label' => 'Shipped', 'icon' => 'fa-plane'],
                    ['label' => 'Out for Delivery', 'icon' => 'fa-truck-fast'],
                    ['label' => 'Delivered', 'icon' => 'fa-house-chimney']
                ];
            ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden order-card" 
                         data-id="<?php echo $order['id']; ?>" 
                         data-status="<?php echo $order['status']; ?>">
                        
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between">
                            <div>
                                <span class="badge bg-primary rounded-pill me-2">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($order['status'] == 'delivered'): ?>
                                    <button class="btn btn-sm btn-outline-warning text-dark rounded-pill celebrate-btn" onclick="triggerConfetti()">
                                        🎉 Celebrate
                                    </button>
                                <?php endif; ?>

                                <a href="generate_receipt.php?order=<?php echo $order['order_number']; ?>" 
                                class="btn btn-outline-dark btn-sm rounded-pill" target="_blank">
                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> Invoice
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-body p-4">
                            <?php if ($order['status'] != 'cancelled'): ?>
                                
                                <div class="stepper-wrapper">
                                    <?php foreach ($steps as $index => $step): 
                                        $class = '';
                                        if ($index < $current_index) $class = 'completed';
                                        elseif ($index == $current_index) $class = 'active';
                                    ?>
                                    <div class="stepper-item <?php echo $class; ?>">
                                        <div class="step-counter">
                                            <?php if ($index < $current_index): ?>
                                                <i class="fa-solid fa-check"></i>
                                            <?php else: ?>
                                                <i class="fa-solid <?php echo $step['icon']; ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="step-name"><?php echo $step['label']; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php else: ?>
                                <div class="alert alert-danger mb-0">
                                    <i class="fa-solid fa-circle-xmark me-2"></i> This order has been cancelled.
                                </div>
                            <?php endif; ?>

                            <div class="row mt-4">
                                <div class="col-md-7">
                                    <h6 class="small fw-bold text-uppercase text-muted mb-3">Order Items</h6>
                                    <?php 
                                        $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                                        $stmt_items->execute([$order['id']]);
                                        $items = $stmt_items->fetchAll();
                                        foreach ($items as $item): 
                                    ?>
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span><?php echo htmlspecialchars($item['product_name']); ?> x<?php echo $item['quantity']; ?></span>
                                            <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="col-md-5 border-start">
                                    <h6 class="small fw-bold text-uppercase text-muted mb-2">Delivery Location</h6>
                                    <div class="delivery-info">
                                        <p class="mb-0 small fw-bold"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <p class="mb-0 small text-muted"><?php echo htmlspecialchars($order['customer_address']); ?></p>
                                        <p class="mb-0 small text-muted">Phone: <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                    </div>

                                    <div class="mt-4 mb-2">
                                        <h6 class="small fw-bold text-uppercase text-muted mb-2">Payment Details</h6>
                                        <div class="d-flex align-items-center">
                                            <i class="fa-regular fa-credit-card me-2 text-secondary"></i>
                                            <span class="fw-medium text-dark">
                                                <?php echo ($order['payment_method'] == 'cod') ? 'Cash on Delivery' : 'Online Payment (Card)'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2 border-top">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Total Amount</span>
                                            <span class="fw-bold text-primary">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <h4 class="text-muted">No orders found.</h4>
        </div>
    <?php endif; ?>
</div>

<script>
    function triggerConfetti() {
        var duration = 2 * 1000;
        var end = Date.now() + duration;
        (function frame() {
            confetti({ particleCount: 4, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#6366f1', '#a855f7'] });
            confetti({ particleCount: 4, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#6366f1', '#a855f7'] });
            if (Date.now() < end) { requestAnimationFrame(frame); }
        }());
    }

    document.addEventListener("DOMContentLoaded", function() {
        const cards = document.querySelectorAll('.order-card');
        cards.forEach(card => {
            const status = card.dataset.status;
            const orderId = card.dataset.id;
            const storageKey = 'celebrated_order_' + orderId;
            if (status === 'delivered' && !localStorage.getItem(storageKey)) {
                triggerConfetti();
                localStorage.setItem(storageKey, 'true');
            }
        });
    });
</script>

</body>
</html>