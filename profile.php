<?php
session_start();
require 'db_connect.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Block Admins from accessing the User Profile
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    echo "<script>alert('Admins cannot access the customer dashboard.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// --- 1. HANDLE ALL FORM SUBMISSIONS FIRST ---

// Profile Info Update
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?")->execute([$name, $phone, $address, $user_id]);
    $_SESSION['user_name'] = $name; 
    header("Location: profile.php?updated=1"); // Force fresh reload
    exit();
}

// Password Change
if (isset($_POST['update_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    if (password_verify($current, $user_data['password'])) {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user_id]);
        header("Location: profile.php?pwd_success=1");
    } else {
        header("Location: profile.php?pwd_error=1");
    }
    exit();
}

// Photo Upload
if (isset($_POST['upload_photo']) && isset($_FILES['photo'])) {
    $target_dir = "img/profiles/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
    $target_file = $target_dir . "user_" . $user_id . "_" . time() . "." . $file_extension;
    
    if(getimagesize($_FILES["photo"]["tmp_name"]) !== false) {
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $old = $stmt->fetchColumn();
            if ($old && file_exists($old)) { unlink($old); }
            
            $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?")->execute([$target_file, $user_id]);
        }
    }
    header("Location: profile.php"); 
    exit();
}

// Photo Removal
if (isset($_POST['remove_photo'])) {
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old = $stmt->fetchColumn();
    if ($old && file_exists($old)) { unlink($old); }
    
    $pdo->prepare("UPDATE users SET profile_photo = NULL WHERE id = ?")->execute([$user_id]);
    header("Location: profile.php"); 
    exit();
}

// Support Ticket Submission
if (isset($_POST['submit_ticket'])) {
    $order_selection = $_POST['order_selection'];
    $custom_subject = $_POST['custom_subject'];
    $message = $_POST['message'];
    
    // Fetch name for the ticket
    $name_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $name_stmt->execute([$user_id]);
    $u_name = $name_stmt->fetchColumn();
    
    $final_subject = ($order_selection === 'general') ? ($custom_subject ? $custom_subject : "General Inquiry") : "Issue with Order #" . $order_selection;
    $pdo->prepare("INSERT INTO complaints (user_id, user_name, subject, message) VALUES (?, ?, ?, ?)")->execute([$user_id, $u_name, $final_subject, $message]);
    header("Location: profile.php?ticket_sent=1");
    exit();
}

// --- 2. NOW FETCH FRESH DATA FOR THE PAGE ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt_orders = $pdo->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY id DESC");
$stmt_orders->execute([$user['email']]);
$my_orders = $stmt_orders->fetchAll();

$stmt_complaints = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY id DESC");
$stmt_complaints->execute([$user_id]);
$tickets = $stmt_complaints->fetchAll();

function getStatusIndex($status) {
    $stages = ['pending', 'dispatched', 'in_transit', 'out_for_delivery', 'delivered'];
    return array_search($status, $stages); 
}

include 'header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<style>
    /* Profile General Styles */
    .profile-header { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius: 20px; padding: 40px; color: white; margin-bottom: 30px; position: relative; }
    .profile-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
    .avatar-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto; }
    .profile-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 10px 20px rgba(0,0,0,0.1); background: #f8fafc; }
    .camera-btn { position: absolute; bottom: 0; right: 0; background: #1e293b; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid white; transition: 0.2s; }
    .camera-btn:hover { background: #6366f1; transform: scale(1.1); }
    .form-control { border-radius: 12px; padding: 12px 15px; background: #f8fafc; border: 2px solid #f1f5f9; }
    .form-control:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
    .nav-pills .nav-link { color: #64748b; border-radius: 12px; padding: 12px 20px; font-weight: 600; margin-bottom: 10px; transition: 0.3s; }
    .nav-pills .nav-link.active { background-color: #6366f1; color: white; }
    .nav-pills .nav-link:hover:not(.active) { background-color: #f1f5f9; }

    /* Stepper Styles (From my_orders.php) */
    .stepper-wrapper { display: flex; justify-content: space-between; margin-bottom: 20px; position: relative; }
    .stepper-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; }
    .stepper-item::before { content: ''; position: absolute; top: 20px; left: -50%; width: 100%; height: 3px; background: #e2e8f0; z-index: -1; }
    .stepper-item::after { content: ''; position: absolute; top: 20px; left: -50%; width: 0%; height: 3px; background: #6366f1; z-index: -1; transition: width 0.5s ease-out; }
    .stepper-item:first-child::before, .stepper-item:first-child::after { content: none; }
    .step-counter { width: 40px; height: 40px; border-radius: 50%; background: #f8fafc; border: 2px solid #e2e8f0; display: flex; justify-content: center; align-items: center; margin-bottom: 6px; z-index: 10; position: relative; transition: all 0.4s; }
    .stepper-item:nth-child(1) { z-index: 5; } .stepper-item:nth-child(2) { z-index: 4; } .stepper-item:nth-child(3) { z-index: 3; } .stepper-item:nth-child(4) { z-index: 2; } .stepper-item:nth-child(5) { z-index: 1; }
    @keyframes fillLine { from { width: 0; } to { width: 100%; } }
    @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 70% { transform: scale(1.2); } 100% { transform: scale(1); opacity: 1; } }
    .stepper-item.completed::after, .stepper-item.active::after { animation: fillLine 0.8s ease-out forwards; }
    .stepper-item:nth-child(2)::after { animation-delay: 0.2s; } .stepper-item:nth-child(3)::after { animation-delay: 1.0s; } .stepper-item:nth-child(4)::after { animation-delay: 1.8s; } .stepper-item:nth-child(5)::after { animation-delay: 2.6s; }
    .stepper-item.completed .step-counter { background-color: #6366f1; border-color: #6366f1; color: white; animation: popIn 0.5s ease-out forwards; }
    .stepper-item.active .step-counter { background-color: white; border-color: #6366f1; color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2); }
    .step-name { font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; margin-top: 5px; }
    .stepper-item.active .step-name { color: #0f172a; font-weight: 800; }
    .stepper-item.completed .step-name { color: #6366f1; }
    .delivery-info { border-left: 3px solid #6366f1; padding-left: 15px; margin-top: 20px; }
</style>

<div class="container py-5">
    
    <div class="profile-header d-flex flex-column flex-md-row align-items-center justify-content-between text-center text-md-start shadow-sm">
        <div class="d-flex flex-column flex-md-row align-items-center gap-4">
            <div class="avatar-wrapper">
                <?php if ($user['profile_photo']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" class="profile-avatar" alt="Profile Photo">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random&color=fff&size=120" class="profile-avatar" alt="Default Avatar">
                <?php endif; ?>
                
                <label for="photo-upload" class="camera-btn" title="Change Photo"><i class="fa-solid fa-camera"></i></label>
                <form id="photo-form" method="POST" enctype="multipart/form-data" class="d-none">
                    <input type="file" name="photo" id="photo-upload" accept="image/*" onchange="document.getElementById('photo-form').submit();">
                    <input type="hidden" name="upload_photo" value="1">
                </form>
            </div>
            <div>
                <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="text-white-50 mb-0"><i class="fa-regular fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge bg-white text-primary mt-2 text-uppercase fw-bold"><?php echo htmlspecialchars($user['role']); ?> Account</span>
            </div>
        </div>
        
        <?php if ($user['profile_photo']): ?>
            <form method="POST" class="mt-4 mt-md-0">
                <button type="submit" name="remove_photo" class="btn btn-light text-danger rounded-pill fw-bold shadow-sm" onclick="return confirm('Remove your profile photo?');">
                    <i class="fa-solid fa-trash-can me-2"></i>Remove Photo
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-md-4 col-lg-3">
            <div class="card profile-card p-3 sticky-top" style="top: 100px;">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                    <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button"><i class="fa-regular fa-user me-2"></i> Personal Details</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#v-pills-orders" type="button"><i class="fa-solid fa-box-open me-2"></i> My Orders</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#v-pills-support" type="button"><i class="fa-solid fa-headset me-2"></i> Support Tickets</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button"><i class="fa-solid fa-shield-halved me-2"></i> Security</button>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">
                
                <div class="tab-pane fade show active" id="v-pills-profile">
                    <div class="card profile-card p-4">
                        <h4 class="fw-bold mb-4">Personal Details</h4>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Email Address</label>
                                    <input type="email" class="form-control text-muted" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Add your phone number">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Default Shipping Address</label>
                                    <textarea name="address" class="form-control" rows="3" placeholder="Enter your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12 mt-4 text-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary rounded-pill px-5 fw-bold">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="v-pills-orders">
                    <div class="card profile-card p-4 mb-4">
                        <h4 class="fw-bold mb-4">Order History</h4>
                        
                        <?php if (count($my_orders) > 0): ?>
                            <div class="row g-4">
                                <?php foreach ($my_orders as $order): 
                                    $current_index = getStatusIndex($order['status']);
                                    $steps = [
                                        ['label' => 'Ordered', 'icon' => 'fa-clipboard-list'],
                                        ['label' => 'Packed', 'icon' => 'fa-box-open'], 
                                        ['label' => 'Shipped', 'icon' => 'fa-plane'],
                                        ['label' => 'Out For Delivery', 'icon' => 'fa-truck-fast'],
                                        ['label' => 'Delivered', 'icon' => 'fa-house-chimney']
                                    ];
                                ?>
                                    <div class="col-12">
                                        <div class="card border border-light shadow-sm rounded-4 overflow-hidden order-card" data-id="<?php echo $order['id']; ?>" data-status="<?php echo $order['status']; ?>">
                                            <div class="card-header bg-light border-bottom p-3 d-flex justify-content-between">
                                                <div>
                                                    <span class="badge bg-primary rounded-pill me-2">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($order['status'] == 'delivered'): ?>
                                                        <button class="btn btn-sm btn-outline-warning text-dark rounded-pill" onclick="triggerConfetti()">🎉 Celebrate</button>
                                                    <?php endif; ?>
                                                    <a href="generate_receipt.php?order=<?php echo $order['order_number']; ?>" class="btn btn-outline-dark btn-sm rounded-pill" target="_blank"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Invoice</a>
                                                </div>
                                            </div>
                                            
                                            <div class="card-body p-4">
                                                <?php if ($order['status'] != 'cancelled'): ?>
                                                    <div class="stepper-wrapper">
                                                        <?php foreach ($steps as $index => $step): 
                                                            $class = '';
                                                            if ($index < $current_index) $class = 'completed';
                                                            elseif ($index == $current_index) $class = 'active';
                                                        ?>
                                                        <div class="stepper-item <?php echo $class; ?>">
                                                            <div class="step-counter">
                                                                <?php if ($index < $current_index): ?> <i class="fa-solid fa-check"></i>
                                                                <?php else: ?> <i class="fa-solid <?php echo $step['icon']; ?>"></i> <?php endif; ?>
                                                            </div>
                                                            <div class="step-name"><?php echo $step['label']; ?></div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-danger mb-0"><i class="fa-solid fa-circle-xmark me-2"></i> This order has been cancelled.</div>
                                                <?php endif; ?>

                                                <div class="row mt-4 pt-3 border-top">
                                                    <div class="col-md-7">
                                                        <h6 class="small fw-bold text-uppercase text-muted mb-3">Items</h6>
                                                        <?php 
                                                            $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                                                            $stmt_items->execute([$order['id']]);
                                                            $items = $stmt_items->fetchAll();
                                                            foreach ($items as $item): 
                                                        ?>
                                                            <div class="d-flex justify-content-between small mb-1">
                                                                <span><?php echo htmlspecialchars($item['product_name']); ?> x<?php echo $item['quantity']; ?></span>
                                                                <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="col-md-5 border-start">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span class="small text-muted">Payment</span>
                                                            <span class="small fw-bold"><?php echo ($order['payment_method'] == 'cod') ? 'COD' : 'Card'; ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span class="fw-bold">Total</span>
                                                            <span class="fw-bold text-primary">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa-solid fa-box-open fs-1 text-muted opacity-25 mb-3"></i>
                                <h5 class="text-muted">No orders found.</h5>
                                <a href="products.php" class="btn btn-outline-primary rounded-pill mt-3">Go Shopping</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="v-pills-support">
                    <div class="card profile-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold m-0">Support Tickets</h4>
                            <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="collapse" data-bs-target="#newTicketForm">
                                <i class="fa-solid fa-plus me-1"></i> New Ticket
                            </button>
                        </div>

                        <div class="collapse mb-4" id="newTicketForm">
                            <div class="p-4 bg-light rounded-4 border">
                                <h6 class="fw-bold mb-3">How can we help?</h6>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Related Order (Optional)</label>
                                        <select name="order_selection" id="orderSelect" class="form-select" onchange="toggleSubjectField()">
                                            <option value="general">General Inquiry / Other</option>
                                            <?php foreach($my_orders as $order): ?>
                                                <option value="<?php echo $order['order_number']; ?>">Order #<?php echo htmlspecialchars($order['order_number']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="subjectField">
                                        <label class="form-label small fw-bold text-muted">Subject</label>
                                        <input type="text" name="custom_subject" class="form-control" placeholder="Briefly describe the issue">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Message</label>
                                        <textarea name="message" class="form-control" rows="4" required placeholder="Details..."></textarea>
                                    </div>
                                    <button type="submit" name="submit_ticket" class="btn btn-dark w-100 rounded-pill">Submit to Support</button>
                                </form>
                            </div>
                        </div>

                        <?php if(count($tickets) > 0): ?>
                            <?php foreach($tickets as $c): ?>
                            <div class="card border border-light shadow-sm mb-3 rounded-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($c['subject']); ?></h6>
                                        <?php if($c['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-success rounded-pill">Resolved</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-secondary small mb-3"><?php echo date('M d, Y h:i A', strtotime($c['created_at'])); ?></p>
                                    <p class="mb-0 text-muted bg-light p-3 rounded"><?php echo htmlspecialchars($c['message']); ?></p>

                                    <?php if($c['admin_reply']): ?>
                                        <div class="mt-3 p-3 bg-success bg-opacity-10 border border-success rounded">
                                            <strong class="text-success small text-uppercase"><i class="fa-solid fa-user-shield me-1"></i> Support Reply:</strong>
                                            <p class="mb-0 mt-1 text-dark"><?php echo htmlspecialchars($c['admin_reply']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">You haven't submitted any tickets yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="v-pills-password">
                    <div class="card profile-card p-4">
                        <h4 class="fw-bold mb-4">Change Password</h4>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" name="update_password" class="btn btn-primary rounded-pill px-5 fw-bold">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // Subject field logic for Support form
    function toggleSubjectField() {
        var select = document.getElementById("orderSelect");
        var subjectField = document.getElementById("subjectField");
        subjectField.style.display = (select.value === "general") ? "block" : "none";
    }

    // Confetti logic for Orders
    function triggerConfetti() {
        var duration = 2 * 1000;
        var end = Date.now() + duration;
        (function frame() {
            confetti({ particleCount: 4, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#6366f1', '#a855f7'] });
            confetti({ particleCount: 4, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#6366f1', '#a855f7'] });
            if (Date.now() < end) { requestAnimationFrame(frame); }
        }());
    }

    // Preserve active tab on page reload (Optional but nice UX)
    document.addEventListener("DOMContentLoaded", function() {
        // Run confetti for newly delivered orders
        document.querySelectorAll('.order-card').forEach(card => {
            if (card.dataset.status === 'delivered' && !localStorage.getItem('celebrated_order_' + card.dataset.id)) {
                triggerConfetti();
                localStorage.setItem('celebrated_order_' + card.dataset.id, 'true');
            }
        });
    });

    // --- Handle Success Popups via URL ---
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('updated')) {
        Swal.fire('Updated!', 'Your profile has been saved.', 'success');
    } else if (urlParams.has('pwd_success')) {
        Swal.fire('Success!', 'Password changed successfully.', 'success');
    } else if (urlParams.has('pwd_error')) {
        Swal.fire('Error!', 'Current password is incorrect.', 'error');
    } else if (urlParams.has('ticket_sent')) {
        Swal.fire('Sent!', 'Ticket created successfully.', 'success');
    }

    // Clean up the URL so the popup doesn't show again if they refresh
    if (window.history.replaceState && urlParams.toString() !== "") {
        window.history.replaceState(null, null, window.location.pathname);
    }
</script>

</body>
</html>