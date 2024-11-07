<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkStudent();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$fee_id = $_GET['id'];

// Fetch fee details with room type and fees
$stmt = $pdo->prepare("
    SELECT f.*, r.room_no, r.room_type,
    CASE 
        WHEN r.room_type = 'Single' THEN '60,000'
        WHEN r.room_type = 'Double' THEN '50,000'
        WHEN r.room_type = 'Triple' THEN '40,000'
        ELSE 'N/A'
    END as room_fees
    FROM fee f
    LEFT JOIN allotment a ON f.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    WHERE f.fee_id = ? AND f.student_id = ?
");
$stmt->execute([$fee_id, $_SESSION['actual_id']]);
$fee = $stmt->fetch();

if (!$fee) {
    header("Location: list.php");
    exit();
}

include '../../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Fee Details</h2>
        <div>
            <?php if($fee['status'] == 'Paid'): ?>
                <button onclick="printReceipt()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            <?php endif; ?>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Fee Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Fee ID:</strong> #<?= $fee['fee_id'] ?></p>
                            <p><strong>Amount:</strong> ₹<?= number_format($fee['amount'], 2) ?></p>
                            <p><strong>Due Date:</strong> <?= date('d-m-Y', strtotime($fee['due_date'])) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= 
                                    $fee['status'] == 'Paid' ? 'success' : 
                                    ($fee['status'] == 'Pending' ? 'warning' : 'danger') 
                                ?>">
                                    <?= $fee['status'] ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Room Number:</strong> <?= $fee['room_no'] ?? 'N/A' ?></p>
                            <p><strong>Room Type:</strong> <?= $fee['room_type'] ? $fee['room_type'] . ' Class' : 'N/A' ?></p>
                            <p><strong>Room Fee:</strong> ₹<?= $fee['room_fees'] ?></p>
                            <?php if ($fee['paid_on']): ?>
                                <p><strong>Paid On:</strong> <?= date('d-m-Y', strtotime($fee['paid_on'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Instructions</h5>
                </div>
                <div class="card-body">
                    <p>Please contact the hostel administration office for payment.</p>
                    <p><strong>Office Hours:</strong> 9:00 AM - 5:00 PM</p>
                    <p><strong>Contact:</strong> +91-XXXXXXXXXX</p>
                    <p><strong>Email:</strong> hostel@example.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Template -->
<div id="receipt" style="display: none;">
    <div style="padding: 20px; border: 2px solid #000; max-width: 400px; margin: 20px auto;">
        <h3 style="text-align: center;">Fee Receipt</h3>
        <hr>
        <p><strong>Receipt No:</strong> #<?= $fee['fee_id'] ?></p>
        <p><strong>Room:</strong> <?= $fee['room_no'] ?? 'N/A' ?></p>
        <p><strong>Room Type:</strong> <?= $fee['room_type'] ? $fee['room_type'] . ' Class' : 'N/A' ?></p>
        <p><strong>Amount:</strong> ₹<?= number_format($fee['amount'], 2) ?></p>
        <p><strong>Due Date:</strong> <?= date('d-m-Y', strtotime($fee['due_date'])) ?></p>
        <p><strong>Paid On:</strong> <?= date('d-m-Y', strtotime($fee['paid_on'])) ?></p>
        <hr>
        <p style="text-align: center;">Thank you for your payment!</p>
        <p style="text-align: center;"><small>This is a computer-generated receipt.</small></p>
    </div>
</div>

<script>
function printReceipt() {
    const printContent = document.getElementById('receipt').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    
    // Reload page after printing to restore functionality
    location.reload();
}
</script>

<?php include '../../includes/student_footer.php'; ?>
