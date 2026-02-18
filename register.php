<?php
session_start();
include 'header.php';
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure Hash

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $password]);
        
        echo "<script>
            Swal.fire('Success', 'Account created! Please login.', 'success')
            .then(() => window.location.href = 'login.php');
        </script>";
    } catch (PDOException $e) {
        echo "<script>Swal.fire('Error', 'Email already exists!', 'error');</script>";
    }
}
?>
<style>
    .register-container {
        min-height: 100vh; /* Full screen height */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
</style>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px; border-radius: 20px;">
        <h3 class="text-center mb-4 fw-bold text-primary">Sign Up</h3>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill">Create Account</button>
            <div class="text-center mt-3">
                <small>Already have an account? <a href="login.php">Login here</a></small>
            </div>
        </form>
    </div>
</div>
</body>
</html>