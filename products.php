<?php
session_start();

// 🔒 SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'header.php';
include 'db_connect.php'; 

// 1. GET SEARCH & SORT PARAMETERS (Fixes Undefined Variable)
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$sort_label = "Featured"; 

// 2. BUILD SQL QUERY WITH SEARCH
// We use WHERE to filter and then add ORDER BY at the end
$sql = "SELECT * FROM products WHERE name LIKE :search";

// 3. HANDLE SORTING LOGIC
if($sort_option == 'price_low') {
    $sql .= " ORDER BY price ASC";
    $sort_label = "Price: Low to High";
} 
elseif($sort_option == 'price_high') {
    $sql .= " ORDER BY price DESC";
    $sort_label = "Price: High to Low";
} 
elseif($sort_option == 'name_asc') {
    $sql .= " ORDER BY name ASC";
    $sort_label = "Name: A-Z";
} else {
    $sql .= " ORDER BY id ASC";
}

// 4. EXECUTE QUERY
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    exit;
}
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 20px;
        padding: 60px 40px;
        color: white;
        margin-bottom: 50px;
        position: relative;
        overflow: hidden;
    }

    .hero-section::after {
        content: ''; position: absolute; top: -50%; right: -10%;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(99,102,241,0.4) 0%, rgba(0,0,0,0) 70%);
        border-radius: 50%; z-index: 0;
    }

    .product-card {
        background: white; border: 1px solid #f1f5f9; border-radius: 20px;
        overflow: hidden; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        height: 100%; display: flex; flex-direction: column;
    }

    .product-card:hover {
        transform: translateY(-8px); 
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        border-color: transparent;
    }

    .img-container {
        position: relative; height: 240px; background: #f8fafc;
        overflow: hidden; display: flex; align-items: center; justify-content: center;
    }

    .img-container img { max-width: 80%; transition: transform 0.5s ease; }
    .product-card:hover .img-container img { transform: scale(1.1) rotate(-2deg); }

    .card-details { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
    .product-cat { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 600; margin-bottom: 8px; }
    .product-title { font-weight: 700; font-size: 1.15rem; margin-bottom: 10px; color: #1e293b; }
    .product-price { font-size: 1.25rem; font-weight: 600; color: #6366f1; margin-bottom: 20px; }

    .btn-add {
        width: 100%; padding: 12px; border: none; background: #f1f5f9;
        color: #1e293b; border-radius: 12px; font-weight: 600;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        transition: all 0.3s ease; margin-top: auto;
    }

    .btn-add:hover { background: #6366f1; color: white; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.25); }
    .btn-add:active { transform: scale(0.98); }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .product-wrapper { opacity: 0; animation: fadeInUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
    .product-wrapper:nth-child(1) { animation-delay: 0.1s; }
    .product-wrapper:nth-child(2) { animation-delay: 0.2s; }
    .product-wrapper:nth-child(3) { animation-delay: 0.3s; }
    .fade-out-grid { opacity: 0; transform: translateY(-10px); transition: all 0.3s ease-out; }

    @media (max-width: 768px) {
        .hero-section { padding: 30px 20px; text-align: center; margin-bottom: 30px; }
        .hero-section h1 { font-size: 1.8rem; }
        .hero-section p { font-size: 0.9rem; margin-bottom: 1rem; }
        .card-details { padding: 15px; }
        .product-title { font-size: 0.95rem; }
        .img-container { height: 160px; }
    }
</style>

<div class="container">
    
    <div class="hero-section text-center text-md-start d-flex align-items-center justify-content-between">
        <div style="z-index: 1;">
            <h1 class="display-5 fw-bold mb-3">Upgrade Your Setup.</h1>
            <p class="lead text-white-50 mb-4">Premium gaming gear created for performance.</p>
        </div>
        <div class="d-none d-md-block" style="z-index: 1;">
            <i class="fa-solid fa-gamepad display-1 text-white-50 opacity-25"></i>
        </div>
    </div>

    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-4">
            <h3 class="fw-bold m-0">Latest Arrivals</h3>
        </div>
        <div class="col-md-5">
            <form action="products.php" method="GET" class="position-relative">
                <input type="text" name="search" class="form-control rounded-pill ps-4 border-0 shadow-sm" 
                    placeholder="Search gear..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary rounded-pill position-absolute top-0 end-0 px-4 h-100">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
        
    <div class="col-md-3 text-md-end">
            <div class="dropdown">
                <button class="btn btn-white bg-white border-0 shadow-sm dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                    Sort by: <strong><?php echo $sort_label; ?></strong>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" id="sort-dropdown">
                    <li><a class="dropdown-item" href="?sort=default&search=<?php echo urlencode($search); ?>">Featured</a></li>
                    <li><a class="dropdown-item" href="?sort=price_low&search=<?php echo urlencode($search); ?>">Price: Low to High</a></li>
                    <li><a class="dropdown-item" href="?sort=price_high&search=<?php echo urlencode($search); ?>">Price: High to Low</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?sort=name_asc&search=<?php echo urlencode($search); ?>">Name: A-Z</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4" id="product-grid">
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $product): ?>
                <div class="col-12 col-sm-6 col-lg-4 product-wrapper">
                    <div class="product-card">
                        <div class="img-container">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>

                        <div class="card-details">
                            <div class="product-cat">Gear</div>
                            <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-price">$<?php echo htmlspecialchars($product['price']); ?></div>
                            
                            <button type="button" 
                                    class="btn-add add-btn" 
                                    data-id="<?php echo $product['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                    data-price="<?php echo $product['price']; ?>">
                                Add to Cart <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <h4 class="text-muted">No products found in the database.</h4>
                <p>Please check your database table.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="store.js"></script>
<script src="main.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sortLinks = document.querySelectorAll('.dropdown-item');
        const productGrid = document.getElementById('product-grid');

        sortLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); 
                const targetUrl = this.href;
                productGrid.classList.add('fade-out-grid');
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 300);
            });
        });
    });
</script>
</body>
</html>