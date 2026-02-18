<?php
session_start();
require_once 'db_connect.php';

// 1. HANDLE ADMIN REPLY (Logic moved to top)
if (isset($_POST['reply_complaint'])) {
    $id = $_POST['complaint_id'];
    $reply = $_POST['reply'];
    
    $stmt = $pdo->prepare("UPDATE complaints SET admin_reply = ?, status = 'resolved' WHERE id = ?");
    $stmt->execute([$reply, $id]);
    
    // Redirect to prevent form resubmission
    header("Location: admin_complaints.php?replied=1");
    exit();
}

// 2. NOW Include the Header
include 'admin_header.php';

// Fetch All Complaints (Pending first)
$complaints = $pdo->query("SELECT * FROM complaints ORDER BY status ASC, created_at DESC")->fetchAll();
?>

<div class="container-fluid">
    <h3 class="fw-bold mb-4 text-dark border-bottom pb-2">Support Tickets Manager</h3>
    
    <div class="row">
        <?php foreach ($complaints as $c): ?>
        <div class="col-12 col-xl-6 mb-4">
            <div class="card border-0 shadow-sm rounded-3 h-100" 
                 style="border-left: 5px solid <?php echo ($c['status']=='pending') ? '#ffc107' : '#198754'; ?> !important;">
                
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($c['subject']); ?></h5>
                            <div class="text-muted small">
                                <i class="fa-solid fa-user me-1"></i> <?php echo htmlspecialchars($c['user_name']); ?> 
                                &bull; <i class="fa-regular fa-clock me-1"></i> <?php echo date('M d, h:i A', strtotime($c['created_at'])); ?>
                            </div>
                        </div>
                        <?php if($c['status'] == 'pending'): ?>
                            <span class="badge bg-warning text-dark">Action Needed</span>
                        <?php else: ?>
                            <span class="badge bg-success">Resolved</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-3 bg-light rounded border mb-3">
                        <small class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Customer Wrote:</small>
                        <p class="mb-0 text-dark"><?php echo htmlspecialchars($c['message']); ?></p>
                    </div>

                    <?php if($c['status'] == 'pending'): ?>
                        <form method="POST">
                            <input type="hidden" name="complaint_id" value="<?php echo $c['id']; ?>">
                            <label class="form-label fw-bold small text-muted">Your Reply:</label>
                            <div class="input-group">
                                <input type="text" name="reply" class="form-control" placeholder="Type response here..." required>
                                <button type="submit" name="reply_complaint" class="btn btn-dark">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Send
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                            <small class="text-uppercase fw-bold text-success" style="font-size: 0.7rem;">You Replied:</small>
                            <p class="mb-0 text-dark"><?php echo htmlspecialchars($c['admin_reply']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Show notification only once using URL flag
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('replied')) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: 'Reply sent to customer.',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
</script>
</body>
</html>