<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'header.php';
require 'db_connect.php';
$user_id = $_SESSION['user_id'];

// Always fetch fresh cart from DB
$stmt = $pdo->prepare("SELECT * FROM user_cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Sync current session
$_SESSION['cart'] = [];
foreach($cart_items as $item) {
    $_SESSION['cart'][$item['product_id']] = [
        'name' => $item['product_name'],
        'price' => $item['price'],
        'qty' => $item['quantity']
    ];
}
$total = 0;
?>

<style>
    .cart-container { padding-top: 40px; }
    .cart-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: white; overflow: hidden; }
    .table thead { background-color: #f8fafc; }
    .table th { border: none; padding: 20px; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
    .table td { padding: 25px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    
    /* Quantity Control Design */
    .qty-control { display: flex; align-items: center; background: #f1f5f9; border-radius: 12px; width: fit-content; padding: 5px; }
    .qty-btn { border: none; background: white; width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s; color: #1e293b; }
    .qty-btn:hover { background: #6366f1; color: white; }
    .qty-val { width: 40px; text-align: center; font-weight: 700; color: #1e293b; }

    .summary-card { border: none; border-radius: 20px; background: #1e293b; color: white; padding: 30px; position: sticky; top: 100px; }
    .btn-checkout { background: #6366f1; border: none; border-radius: 12px; padding: 15px; font-weight: 700; transition: 0.3s; }
    .btn-checkout:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); }
</style>

<div class="container cart-container">
    <div class="row g-4">
        <div class="col-12 mb-2">
            <h2 class="fw-bold"><i class="fa-solid fa-cart-shopping text-primary me-2"></i> Your Shopping Bag</h2>
        </div>

        <div class="col-lg-8">
            <div class="cart-card">
                <div class="table-responsive">
                    <table class="table align-middle m-0">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cart_items)): ?>
                                <?php foreach ($cart_items as $item): 
                                    $id = $item['product_id'];
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $total += $itemTotal;
                                ?>
                                    <tr id="row-<?php echo $id; ?>">
                                        <td><div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center bg-light rounded-pill p-1" style="width: fit-content;">
                                                <button class="btn btn-sm btn-white rounded-circle qty-btn" data-id="<?php echo $id; ?>" data-change="-1">
                                                    <i class="fa-solid fa-minus"></i>
                                                </button>
                                                <span class="px-3 fw-bold" id="qty-<?php echo $id; ?>"><?php echo $item['quantity']; ?></span>
                                                <button class="btn btn-sm btn-white rounded-circle qty-btn" data-id="<?php echo $id; ?>" data-change="1">
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            $<span id="subtotal-<?php echo $id; ?>"><?php echo number_format($itemTotal, 2); ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-link text-danger remove-btn" data-id="<?php echo $id; ?>">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <img src="img/empty-cart.png" width="80" class="mb-3 opacity-25">
                                        <p class="text-muted">Your cart is feeling light.</p>
                                        <a href="products.php" class="btn btn-outline-primary btn-sm rounded-pill">Go Shopping</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="summary-card">
                <h4 class="fw-bold mb-4">Summary</h4>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-white-50">Subtotal</span>
                    <span>$<span id="summary-subtotal"><?php echo number_format($total, 2); ?></span></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-white-50">Shipping</span>
                    <span class="text-success">FREE</span>
                </div>
                <hr class="my-4" style="opacity: 0.1;">
                <div class="d-flex justify-content-between mb-4">
                    <span class="h4 fw-bold">Total</span>
                    <span class="h4 fw-bold text-primary">$<span id="summary-total"><?php echo number_format($total, 2); ?></span></span>
                </div>
                <a href="checkout.php" class="btn btn-checkout w-100 text-white">
                    Checkout Now <i class="fa-solid fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="store.js"></script>
<script src="main.js"></script>
</body>
</html>