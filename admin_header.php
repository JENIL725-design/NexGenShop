<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
        }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f3f4f6; 
            overflow-x: hidden; 
        }

        /* SIDEBAR STYLING */
        .sidebar { 
            width: var(--sidebar-width); 
            height: 100vh; 
            position: fixed; 
            top: 0; left: 0; 
            background: #0f172a; 
            color: white; 
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 30px;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            color: #94a3b8;
            padding: 16px 30px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
        }

        .nav-link i { width: 25px; font-size: 1.1rem; }

        .nav-link:hover { color: white; background: rgba(255,255,255,0.03); }

        .nav-link.active {
            color: #fff;
            background: rgba(99, 102, 241, 0.1);
            border-left-color: #6366f1;
        }

        /* MAIN CONTENT WRAPPER */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-layer-group me-2 text-primary"></i>NexGen
    </div>
    
    <div class="d-flex flex-column flex-grow-1">
        <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='admin_dashboard.php'?'active':''; ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        <a href="admin_products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='admin_products.php'?'active':''; ?>">
            <i class="fa-solid fa-box-open"></i> Products
        </a>
        <a href="admin_orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='admin_orders.php'?'active':''; ?>">
            <i class="fa-solid fa-truck-fast"></i> Orders
        </a>
        <a href="admin_complaints.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='admin_complaints.php'?'active':''; ?>">
            <i class="fa-solid fa-envelope-open-text"></i> Support
        </a>
    </div>

    <div class="p-3">
        <a href="products.php" target="_blank" class="nav-link text-white bg-dark rounded mb-2">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Live Store
        </a>
        <a href="logout.php" class="nav-link text-danger">
            <i class="fa-solid fa-power-off"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <button class="btn btn-dark d-md-none mb-4" onclick="document.getElementById('sidebar').classList.toggle('show')">
        <i class="fa-solid fa-bars"></i>
    </button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>