<?php
session_start();
include 'header.php';
require 'db_connect.php';

// HANDLE LOGIN LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        $redirectTarget = ($user['role'] === 'admin') ? 'admin_dashboard.php' : 'products.php';
        $welcomeMsg = ($user['role'] === 'admin') ? 'Welcome Admin' : 'Welcome back, ' . $user['name'];

        echo "<script>
            Swal.fire({
                icon: 'success',
                title: '$welcomeMsg',
                showConfirmButton: false,
                timer: 1500
            }).then(() => window.location.href = '$redirectTarget');
        </script>";
    } else {
        echo "<script>Swal.fire('Error', 'Invalid credentials', 'error');</script>";
    }
}
?>

<style>
/* Updated CSS for login.php */
    .login-container {
        /* Was: min-height: calc(100vh - 80px); */
        min-height: 100vh; /* Now use full height since header is gone */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-wrapper {
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        overflow: hidden;
        display: flex;
        width: 100%;
        max-width: 1000px;
        min-height: 600px;
    }

    .login-left {
        flex: 1;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        padding: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: white;
        position: relative;
    }

    .login-left::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: url('https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&q=80') center/cover;
        opacity: 0.1; /* Subtle texture */
    }

    .login-right {
        flex: 1;
        padding: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .form-control {
        border-radius: 12px;
        padding: 12px 15px;
        border: 2px solid #f1f5f9;
        background: #f8fafc;
        font-size: 0.95rem;
    }

    .form-control:focus {
        border-color: #6366f1;
        background: white;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .login-left { display: none; } /* Hide image on mobile */
        .login-right { padding: 40px 20px; }
        .login-wrapper { max-width: 500px; min-height: auto; }
    }
</style>

<div class="container-fluid login-container">
    <div class="login-wrapper">
        
        <div class="login-left">
            <h2 class="display-5 fw-bold mb-3">Upgrade Your<br>Game.</h2>
            <p class="lead opacity-75 mb-5">Join NexGenStore and access exclusive deals on premium gaming gear.</p>
            <div>
                <small class="text-white-50 uppercase tracking-widest">Trusted by 10,000+ Gamers</small>
            </div>
        </div>

        <div class="login-right">
            <div class="mb-5">
                <h2 class="fw-bold text-dark">Welcome Back! 👋</h2>
                <p class="text-muted">Please enter your details to sign in.</p>
            </div>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase text-muted">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="fa-solid fa-envelope text-secondary"></i></span>
                        <input type="email" name="email" class="form-control border-start-0" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase text-muted">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="fa-solid fa-lock text-secondary"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-lg mb-4">
                    Sign In <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>

                <div class="text-center">
                    <p class="text-muted">Don't have an account? <a href="register.php" class="text-primary fw-bold text-decoration-none">Create Account</a></p>
                </div>
            </form>
        </div>

    </div>
</div>
</body>
</html>