<?php
session_start();
require 'db_connect.php'; // 1. Connect to DB

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. ✨ FORCE SYNC: Get latest cart data from Database
$stmt = $pdo->prepare("SELECT * FROM user_cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// 3. Rebuild Session & Calculate Total
$_SESSION['cart'] = [];
$total = 0;

foreach($cart_items as $item) {
    $_SESSION['cart'][$item['product_id']] = [
        'name' => $item['product_name'],
        'price' => $item['price'],
        'qty' => $item['quantity']
    ];
    $total += ($item['price'] * $item['quantity']);
}

include 'header.php'; // Include header after logic

// Redirect if empty
if (empty($_SESSION['cart'])) {
    echo "<script>window.location.href='products.php';</script>";
    exit();
}
?>

<style>
    body { background-color: #f3f4f6; }
    .checkout-wrapper { padding-top: 30px; padding-bottom: 80px; }
    
    /* Step Wizard */
    .steps-container { display: flex; justify-content: center; margin-bottom: 40px; }
    .step { display: flex; align-items: center; color: #94a3b8; font-weight: 500; font-size: 0.9rem; }
    .step.active { color: #6366f1; font-weight: 700; }
    .step-icon { width: 30px; height: 30px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-size: 0.8rem; }
    .step.active .step-icon { background: #6366f1; color: white; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2); }
    .step-line { width: 50px; height: 2px; background: #e2e8f0; margin: 0 15px; }

    /* Form Styling */
    .form-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); margin-bottom: 25px; }
    .section-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 25px; display: flex; align-items: center; }
    .section-title i { margin-right: 10px; color: #6366f1; }
    .form-label { font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 8px; display: block; }
    .form-control { border: 2px solid #f1f5f9; border-radius: 12px; padding: 12px 15px; font-size: 0.95rem; transition: all 0.3s; background: #f8fafc; }
    .form-control:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

    /* Payment Options */
    .payment-option { border: 2px solid #e2e8f0; border-radius: 15px; padding: 20px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
    .payment-option:hover { border-color: #cbd5e1; }
    .payment-option.active { border-color: #6366f1; background-color: #f5f3ff; }
    .radio-custom { width: 20px; height: 20px; border: 2px solid #cbd5e1; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative; }
    .payment-option.active .radio-custom::after { content: ''; width: 10px; height: 10px; background: #6366f1; border-radius: 50%; display: block; }

    /* Card Details Form (Hidden by default) */
    #card-details { display: none; background: #f8fafc; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; margin-top: 10px; animation: slideDown 0.3s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* Order Summary Sidebar */
    .summary-sidebar { position: sticky; top: 100px; }
    .summary-card { background: #1e293b; color: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(30, 41, 59, 0.2); }
    .cart-item { display: flex; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .item-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px; }
    .btn-place-order { background: #6366f1; color: white; width: 100%; padding: 15px; border-radius: 12px; border: none; font-weight: 600; margin-top: 25px; transition: all 0.3s; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); }
    .btn-place-order:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5); }
</style>

<div class="container checkout-wrapper">
    
    <div class="steps-container">
        <div class="step"><div class="step-icon"><i class="fa-solid fa-check"></i></div>Cart</div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-icon">2</div>Shipping</div>
        <div class="step-line"></div>
        <div class="step"><div class="step-icon">3</div>Done</div>
    </div>

    <div class="row g-5">
        <div class="col-lg-7">
            <form id="checkout-form">
                
                <div class="form-card">
                    <div class="section-title"><i class="fa-solid fa-truck-fast"></i> Shipping Details</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="fullname" class="form-control" placeholder="John Doe" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="123 456 7890" maxlength="10" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''; ?>" 
                               placeholder="john@example.com" required 
                               <?php echo isset($_SESSION['user_email']) ? 'readonly' : ''; ?>>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Street Address</label>
                            <input type="text" name="address" class="form-control" placeholder="123 Main St, Apt 4B" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" class="form-control" placeholder="10001" maxlength="6" required>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="section-title"><i class="fa-regular fa-credit-card"></i> Payment Method</div>
                    
                    <div class="payment-option active" id="opt-cod" onclick="selectPayment('cod')">
                        <div class="d-flex align-items-center">
                            <div class="radio-custom me-3"></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Cash on Delivery</h6>
                                <small class="text-muted">Pay when you receive</small>
                            </div>
                        </div>
                        <i class="fa-solid fa-money-bill-wave text-success fs-4"></i>
                        <input type="radio" name="payment" id="payment_cod" value="cod" checked hidden>
                    </div>

                    <div class="payment-option" id="opt-card" onclick="selectPayment('card')">
                        <div class="d-flex align-items-center">
                            <div class="radio-custom me-3"></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Debit / Credit Card</h6>
                                <small class="text-muted">Visa, Mastercard, RuPay</small>
                            </div>
                        </div>
                        <div class="text-secondary fs-4">
                            <i class="fa-brands fa-cc-visa me-1"></i>
                            <i class="fa-brands fa-cc-mastercard"></i>
                        </div>
                        <input type="radio" name="payment" id="payment_card" value="credit_card" hidden>
                    </div>

                    <div id="card-details">
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-credit-card"></i></span>
                                <input type="text" id="card_number" class="form-control border-start-0" placeholder="0000 0000 0000 0000" maxlength="19">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" id="card_expiry" class="form-control" placeholder="MM / YY" maxlength="5">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CVV</label>
                                <input type="password" id="card_cvv" class="form-control" placeholder="123" maxlength="3">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name on Card</label>
                            <input type="text" id="card_name" class="form-control" placeholder="JOHN DOE">
                        </div>
                    </div>

                </div>

            </form>
        </div>

        <div class="col-lg-5">
            <div class="summary-sidebar">
                <div class="summary-card">
                    <h4 class="fw-bold mb-4">Your Order</h4>
                    <div style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="cart-item">
                                <div class="item-icon">📦</div>
                                <div>
                                    <div class="fw-medium small"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="text-white-50 small">Qty: <?php echo $item['qty']; ?></div>
                                </div>
                                <div class="ms-auto fw-bold">$<?php echo number_format($item['price'] * $item['qty'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <div class="d-flex justify-content-between mb-2 small text-white-50"><span>Subtotal</span><span>$<?php echo number_format($total, 2); ?></span></div>
                        <div class="d-flex justify-content-between mb-2 small text-white-50"><span>Shipping</span><span class="text-success">Free</span></div>
                        <div class="d-flex justify-content-between mt-3 fs-5 fw-bold"><span>Total to Pay</span><span>$<?php echo number_format($total, 2); ?></span></div>
                    </div>
                    <button type="button" class="btn-place-order" id="place-order-btn">Confirm Order <i class="fa-solid fa-arrow-right ms-2"></i></button>
                    <div class="text-center mt-3"><a href="cart_view.php" class="text-white-50 text-decoration-none small"><i class="fa-solid fa-arrow-left me-1"></i> Return to Cart</a></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="store.js"></script>
<script>
    // 1. Handle Payment Selection Logic
    function selectPayment(method) {
        // Remove active class from all
        document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('active'));
        
        // Add active class to clicked
        document.getElementById('opt-' + method).classList.add('active');
        
        // Check the hidden radio button
        document.getElementById('payment_' + method).checked = true;

        // Show/Hide Card Details
        const cardForm = document.getElementById('card-details');
        const cardInputs = cardForm.querySelectorAll('input');

        if (method === 'card') {
            cardForm.style.display = 'block';
            // Make inputs required
            cardInputs.forEach(input => input.setAttribute('required', 'true'));
        } else {
            cardForm.style.display = 'none';
            // Remove required so form validates for COD
            cardInputs.forEach(input => input.removeAttribute('required'));
            cardInputs.forEach(input => input.value = ''); // Clear values
        }
    }

    // 2. Format Card Number (Spaces every 4 digits)
    document.getElementById('card_number').addEventListener('input', function (e) {
        e.target.value = e.target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();
    });

    // 3. Handle Order Submission
    document.addEventListener("DOMContentLoaded", function() {
        const placeOrderBtn = document.getElementById('place-order-btn');
        
        placeOrderBtn.addEventListener('click', function() {
            const form = document.getElementById('checkout-form');
            
            // Check HTML5 Validation
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            formData.append('action', 'checkout');
            
            // Show Loading Spinner
            Swal.fire({
                title: 'Processing Order',
                text: 'Securely contacting server...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            // Simulate slight delay for "Card Processing" feel
            setTimeout(() => {
                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (typeof CartStore !== 'undefined') CartStore.setCount(0);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Placed! 🎉',
                            text: 'Order ID: #' + (data.message.split('#')[1] || 'Pending'),
                            confirmButtonColor: '#6366f1',
                            confirmButtonText: 'View My Orders'
                        }).then(() => {
                            window.location.href = 'my_orders.php';
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Error', 'Connection failed', 'error');
                });
            }, 1000); 
        });
    });
</script>

</body>
</html>