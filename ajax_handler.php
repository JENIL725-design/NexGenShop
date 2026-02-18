<?php
session_start();
header('Content-Type: application/json');
require 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// HELPER: Get Fresh Totals
function getCartData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count, SUM(price * quantity) as total FROM user_cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch();
    return ['count' => (int)$res['count'], 'total' => (float)$res['total']];
}

// 1. ADD ITEM
if ($action === 'add') {
    $id = $_POST['id']; $name = $_POST['name']; $price = $_POST['price'];
    
    $stmt = $pdo->prepare("SELECT id FROM user_cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $id]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE user_cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?")->execute([$user_id, $id]);
    } else {
        $pdo->prepare("INSERT INTO user_cart (user_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, 1)")->execute([$user_id, $id, $name, $price]);
    }
    
    $data = getCartData($pdo, $user_id);
    echo json_encode(['status' => 'success', 'message' => 'Added!', 'cart_count' => $data['count']]);
    exit;
}

// 2. UPDATE QUANTITY (+/-)
if ($action === 'update_qty') {
    $id = $_POST['id'];
    $qty = (int)$_POST['qty'];

    if ($qty > 0) {
        $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE user_id = ? AND product_id = ?")->execute([$qty, $user_id, $id]);
    } else {
        $pdo->prepare("DELETE FROM user_cart WHERE user_id = ? AND product_id = ?")->execute([$user_id, $id]);
    }

    $data = getCartData($pdo, $user_id);

    // Get specific item subtotal for the row
    $stmt = $pdo->prepare("SELECT price, quantity FROM user_cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $id]);
    $item = $stmt->fetch();
    $item_subtotal = $item ? ($item['price'] * $item['quantity']) : 0;

    echo json_encode([
        'status' => 'success',
        'cart_count' => $data['count'],
        'new_total' => number_format($data['total'], 2),
        'new_item_subtotal' => number_format($item_subtotal, 2)
    ]);
    exit;
}

// 3. REMOVE ITEM
if ($action === 'remove') {
    $id = $_POST['id'];
    $pdo->prepare("DELETE FROM user_cart WHERE user_id = ? AND product_id = ?")->execute([$user_id, $id]);
    
    $data = getCartData($pdo, $user_id);
    echo json_encode([
        'status' => 'success',
        'cart_count' => $data['count'],
        'new_total' => number_format($data['total'], 2)
    ]);
    exit;
}

// ✨ 4. NEW: HANDLE CHECKOUT (This was missing!)
if ($action === 'checkout') {
    try {
        $pdo->beginTransaction();

        // 1. Calculate Total securely from DB
        $stmt = $pdo->prepare("SELECT * FROM user_cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll();

        if (count($cart_items) === 0) {
            throw new Exception("Cart is empty!");
        }

        $subtotal = 0;
        foreach ($cart_items as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
        }

        // ✨ ADD 18% GST to the final total saved in DB
        $total_with_tax = $subtotal + ($subtotal * 0.18);

        // 2. ✨ ROBUST ORDER NUMBER GENERATION
        // We use a loop to ensure we never hit a "Duplicate Entry" error again
        $order_exists = true;
        $order_number = "";
        
        while ($order_exists) {
            // Generates a 6-character random hex string (e.g., ORD-A1B2C3)
            $order_number = "ORD-" . strtoupper(bin2hex(openssl_random_pseudo_bytes(3)));
            
            $check = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
            $check->execute([$order_number]);
            if (!$check->fetch()) {
                $order_exists = false;
            }
        }

        $fullname = $_POST['fullname'];
        $email = $_SESSION['user_email']; // Use session email for security
        $phone = $_POST['phone'];
        $address = $_POST['address'] . ", " . $_POST['pincode'];
        $payment_method = $_POST['payment'];

        // 3. Insert into Orders Table
        $sql = "INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, customer_address, total_amount, payment_method, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_number, $fullname, $email, $phone, $address, $total_with_tax, $payment_method]);
        $order_id = $pdo->lastInsertId();

        // 4. Insert Order Items
        $sql_items = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)";
        $stmt_items = $pdo->prepare($sql_items);
        
        foreach ($cart_items as $item) {
            $stmt_items->execute([$order_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['price']]);
        }

        // 5. Clear User Cart
        $pdo->prepare("DELETE FROM user_cart WHERE user_id = ?")->execute([$user_id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Order Placed #' . $order_number]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Transaction Failed: ' . $e->getMessage()]);
    }
    exit;
}
?>