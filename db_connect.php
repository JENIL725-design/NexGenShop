<?php
// db_connect.php
$host = 'localhost';
$dbname = 'nexgen_shop';
$username = 'root';      // Default XAMPP username
$password = '';          // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Enable error reporting for debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>