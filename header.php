<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Connect to DB (Use require_once to avoid double connection errors)
require_once 'db_connect.php';

$current_page = basename($_SERVER['PHP_SELF']);
$hide_nav = in_array($current_page, ['login.php', 'register.php']);

// 2. ✨ FORCE CART COUNT FROM DATABASE
$cart_count = 0;

if (isset($_SESSION['user_id'])) {
    // If logged in, count items from the database
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM user_cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetchColumn(); // Gets the total quantity directly
    
    // If null (empty cart), set to 0
    if ($cart_count === false || $cart_count === null) {
        $cart_count = 0;
    }
} else {
    // If guest (not logged in), use session count
    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Shop</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
<style>
    :root {
        --primary-color: #6366f1;
        --secondary-color: #1e293b;
        --bg-color: #f8fafc;
        --text-dark: #0f172a;
    }

    body {
        background-color: var(--bg-color);
        font-family: 'Poppins', sans-serif;
        color: var(--text-dark);
        overflow-x: hidden;
    }

    .navbar {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 15px 0;
        z-index: 1050;
        position: sticky;
        top: 0;
    }

    .navbar-brand {
        font-weight: 800;
        color: var(--secondary-color);
        font-size: 1.6rem;
        letter-spacing: -0.5px;
    }

    .nav-link {
        color: #64748b;
        font-weight: 500;
        margin: 0 10px;
        transition: color 0.3s;
        font-size: 0.95rem;
        cursor: pointer;
    }

    .nav-link:hover {
        color: var(--primary-color);
    }

    .btn-nav-cart {
        background: var(--primary-color);
        color: white !important;
        border-radius: 50px;
        padding: 10px 24px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
    }

    .btn-nav-cart:hover {
        background: #4f46e5;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4);
    }

    .main-content {
        min-height: 80vh;
    }
    
    .dropdown-menu {
        margin-top: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border-radius: 12px;
    }
</style>
</head>
<body>

<?php if (!$hide_nav): ?>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="<?php echo isset($_SESSION['user_id']) ? 'products.php' : 'login.php'; ?>">
            <i class="fa-solid fa-layer-group text-primary me-2"></i>NexGenStore<span class="text-primary">.</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="fa-solid fa-bars"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-3">

                <li class="nav-item">
                    <a class="nav-link btn-nav-cart" href="cart_view.php">
                        <i class="fa-solid fa-bag-shopping me-1"></i> Cart
                        <span id="cart-count" class="badge bg-white text-dark rounded-pill ms-1">
                            <?php echo $cart_count; ?>
                        </span>
                    </a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold text-primary" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <li><a class="dropdown-item fw-bold text-purple" href="admin_dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Admin Panel</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>

                            <li><a class="dropdown-item" href="my_orders.php"><i class="fa-solid fa-box me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="contact_us.php"><i class="fa-solid fa-headset me-2"></i>Contact Support</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-primary rounded-pill px-4" href="login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<div class="main-content">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>