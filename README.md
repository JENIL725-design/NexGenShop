NexGen Store - Premium Gaming Gear E-Commerce 🎮
NexGen Store is a modern, full-stack PHP e-commerce platform designed for high-performance gaming gear. It features a responsive UI, real-time cart updates, a multi-stage order tracking system, and a robust admin management suite.

🚀 Key Features
Customer Experience
Dynamic Product Feed: Real-time search and sorting by price or name.

Modern Shopping Cart: AJAX-driven cart management with persistent database storage.

Secure Checkout: Supports multiple payment methods (COD/Card) with 18% GST calculation.

Advanced Order Tracking: Visual 5-stage animated progress tracker (Ordered → Packed → Shipped → Out for Delivery → Delivered).

Support Tickets: Integrated system to raise complaints linked directly to specific order IDs.

Administrative Suite
Business Dashboard: Real-time statistics for total revenue, order volume, and pending support tickets.

Inventory Management: Full CRUD (Create, Read, Update, Delete) capability for products with image upload support.

Fulfillment Control: One-click status updates for orders with modern, color-coded action buttons.

Customer Support: Dedicated interface to reply to user inquiries and resolve tickets.

🛠️ Tech Stack
Backend: PHP (PDO for secure database interactions).

Database: MySQL.

Frontend: Bootstrap 5, FontAwesome 6, and Custom CSS animations.

Logic: JavaScript (Custom Observer Pattern for state management) and SweetAlert2 for notifications.

📦 Installation & Setup
1. Prerequisites
XAMPP or WAMP (PHP 7.4+ recommended).

MySQL database.

2. Database Configuration
Open phpMyAdmin.

Create a new database named nexgen_shop.

Import the provided nexgen_shop.sql file.

Ensure db_connect.php matches your local server credentials:

PHP
$host = 'localhost';
$dbname = 'nexgen_shop';
$username = 'root'; 
$password = ''; 
3. File Setup
Clone or copy the project files into your htdocs folder.

Ensure there is an img/ directory for product image uploads.

Navigate to http://localhost/YOUR_PROJECT_FOLDER/login.php in your browser.

📂 Project Structure
header.php & admin_header.php: Navigation and session security.

ajax_handler.php: Backend logic for cart and checkout transactions.

store.js & main.js: Frontend cart state and event handling.

generate_receipt.php: Print-ready invoices with tax details.

🔐 Credentials (Initial)
Admin: jenil@gmail.com.

User: jenil1@gmail.com.

Note: Passwords are encrypted using BCRYPT.
