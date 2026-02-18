<?php
session_start();
require_once 'db_connect.php'; // 1. Connect to DB first

// 🔒 SECURITY CHECK (Redirects must happen before HTML output)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. HANDLE FORM SUBMISSION (Logic moved to Top)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_selection = $_POST['order_selection'];
    $custom_subject = $_POST['custom_subject'];
    $message = $_POST['message'];
    
    // Determine the final subject
    if ($order_selection === 'general') {
        $final_subject = $custom_subject ? $custom_subject : "General Inquiry";
    } else {
        $final_subject = "Issue with Order #" . $order_selection;
    }

    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'];

    $stmt = $pdo->prepare("INSERT INTO complaints (user_id, user_name, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_name, $final_subject, $message]);
    
    // This redirect will now work because no HTML has been sent yet
    header("Location: contact_us.php?success=1");
    exit();
}

// 3. FETCH USER'S ORDERS (For the Dropdown)
$email = $_SESSION['user_email'];
$order_stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY id DESC");
$order_stmt->execute([$email]);
$my_orders = $order_stmt->fetchAll();

// 4. NOW Include the Header (Outputs HTML)
include 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="card shadow-lg border-0 rounded-4 mb-5">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary d-inline-flex p-3 rounded-circle mb-3">
                            <i class="fa-solid fa-headset fs-2"></i>
                        </div>
                        <h2 class="fw-bold">Contact Support</h2>
                        <p class="text-muted">Select an order or ask a general question.</p>
                    </div>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Order (Optional)</label>
                            <select name="order_selection" id="orderSelect" class="form-select form-select-lg bg-light border-0" onchange="toggleSubjectField()">
                                <option value="general">General Inquiry / Other</option>
                                <?php foreach($my_orders as $order): ?>
                                    <option value="<?php echo $order['order_number']; ?>">
                                        Order #<?php echo htmlspecialchars($order['order_number']); ?> - $<?php echo $order['total_amount']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3" id="subjectField">
                            <label class="form-label fw-bold">Subject</label>
                            <input type="text" name="custom_subject" class="form-control form-control-lg bg-light border-0" placeholder="e.g. Account Issue...">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Message</label>
                            <textarea name="message" class="form-control form-control-lg bg-light border-0" rows="5" placeholder="Describe your issue..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">
                            Submit Ticket <i class="fa-solid fa-paper-plane ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>

            <h4 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2"></i>Your Ticket History</h4>
            
            <?php 
            $my_complaints = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY id DESC");
            $my_complaints->execute([$_SESSION['user_id']]);
            $tickets = $my_complaints->fetchAll();
            ?>

            <?php if(count($tickets) > 0): ?>
                <?php foreach($tickets as $c): ?>
                <div class="card border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($c['subject']); ?></h5>
                            <?php if($c['status'] == 'pending'): ?>
                                <span class="badge bg-warning text-dark rounded-pill">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-success rounded-pill">Resolved</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-secondary small mb-3"><?php echo $c['created_at']; ?></p>
                        <p class="mb-0 text-muted bg-light p-3 rounded"><?php echo htmlspecialchars($c['message']); ?></p>

                        <?php if($c['admin_reply']): ?>
                            <div class="mt-3 p-3 bg-success bg-opacity-10 border border-success rounded">
                                <strong class="text-success"><i class="fa-solid fa-user-shield me-2"></i>Support Team:</strong>
                                <p class="mb-0 mt-1 text-dark"><?php echo htmlspecialchars($c['admin_reply']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-light text-center">You haven't submitted any tickets yet.</div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    function toggleSubjectField() {
        var select = document.getElementById("orderSelect");
        var subjectField = document.getElementById("subjectField");
        
        if (select.value === "general") {
            subjectField.style.display = "block";
        } else {
            subjectField.style.display = "none";
        }
    }

    // Success Popup Logic
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        Swal.fire({
            icon: 'success',
            title: 'Sent!',
            text: 'Ticket created successfully.',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
</script>
</body>
</html>