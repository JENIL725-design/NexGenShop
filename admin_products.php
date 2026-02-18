<?php
include 'admin_header.php';
require 'db_connect.php';

// 1. ADD PRODUCT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $target_dir = "img/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $target_file]);
        echo "<script>Swal.fire('Success', 'Product added!', 'success');</script>";
    } else {
        echo "<script>Swal.fire('Error', 'Image upload failed.', 'error');</script>";
    }
}

// 2. EDIT PRODUCT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $image_path = $_POST['current_image'];

    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "img/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $price, $image_path, $id]);
    echo "<script>Swal.fire('Updated!', 'Product saved.', 'success').then(() => window.location.href='admin_products.php');</script>";
}

// 3. DELETE PRODUCT
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    echo "<script>window.location.href='admin_products.php';</script>";
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>

<style>
    .table-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden; }
    .table thead th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 18px; }
    .table tbody td { padding: 15px 18px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    
    .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

    /* Circular Action Buttons */
    .btn-action {
        width: 35px; height: 35px; border-radius: 50%; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        color: white; transition: 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .btn-action:hover { transform: translateY(-3px); }
    .btn-edit { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .btn-delete { background: linear-gradient(135deg, #ef4444, #dc2626); }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Product Inventory</h3>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fa-solid fa-plus me-2"></i> Add Product
        </button>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>Image</th><th>Product Name</th><th>Price</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><img src="<?php echo $p['image']; ?>" class="product-img"></td>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($p['name']); ?></td>
                        <td class="text-secondary fw-bold">$<?php echo $p['price']; ?></td>
                        <td class="text-end">
                            <button class="btn-action btn-edit me-1 edit-btn" 
                                    data-id="<?php echo $p['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($p['name']); ?>" 
                                    data-price="<?php echo $p['price']; ?>"
                                    data-image="<?php echo $p['image']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editProductModal">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <a href="?delete=<?php echo $p['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this product?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">Product Name</label><input type="text" name="name" class="form-control rounded-3" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">Price ($)</label><input type="number" step="0.01" name="price" class="form-control rounded-3" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">Product Image</label><input type="file" name="image" class="form-control rounded-3" required></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="add_product" class="btn btn-primary rounded-pill px-4 w-100">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">Product Name</label><input type="text" name="name" id="edit_name" class="form-control rounded-3" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">Price ($)</label><input type="number" step="0.01" name="price" id="edit_price" class="form-control rounded-3" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">New Image (Optional)</label><input type="file" name="image" class="form-control rounded-3"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="edit_product" class="btn btn-primary rounded-pill px-4 w-100">Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_price').value = this.dataset.price;
            document.getElementById('edit_current_image').value = this.dataset.image;
        });
    });
</script>

</div> 
</div> 
</body>
</html>