<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$fee_id = $_GET['id'];

// Fetch fee details with related information
$stmt = $pdo->prepare("
    SELECT f.*, 
           s.first_name, s.last_name, s.phone_no,
           r.room_no, r.room_type,
           a.allotment_date, a.end_date
    FROM fee f
    JOIN student s ON f.student_id = s.student_id
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    WHERE f.fee_id = ?
");
$stmt->execute([$fee_id]);
$fee = $stmt->fetch();

if (!$fee) {
    header("Location: list.php");
    exit();
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>View Fee Details</h2>
        <div>
            <?php if ($fee['status'] == 'Pending'): ?>
                <button type="button" class="btn btn-success" 
                        onclick="confirmPayment(<?= $fee_id ?>)">
                    <i class="fas fa-check"></i> Mark as Paid
                </button>
            <?php endif; ?>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Fee Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="150">Fee ID</th>
                            <td><?= $fee['fee_id'] ?></td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>₹<?= number_format($fee['amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($fee['status']) {
                                        'Paid' => 'success',
                                        'Pending' => 'warning',
                                        'Overdue' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?= $fee['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Due Date</th>
                            <td><?= date('d-m-Y', strtotime($fee['due_date'])) ?></td>
                        </tr>
                        <?php if ($fee['paid_on']): ?>
                        <tr>
                            <th>Paid On</th>
                            <td><?= date('d-m-Y', strtotime($fee['paid_on'])) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="150">Name</th>
                            <td>
                                <?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?= $fee['phone_no'] ?></td>
                        </tr>
                        <?php if ($fee['room_no']): ?>
                        <tr>
                            <th>Room</th>
                            <td>
                                Room <?= $fee['room_no'] ?> (<?= $fee['room_type'] ?> Class)
                            </td>
                        </tr>
                        <tr>
                            <th>Allotment Period</th>
                            <td>
                                <?= date('d-m-Y', strtotime($fee['allotment_date'])) ?> to 
                                <?= date('d-m-Y', strtotime($fee['end_date'])) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmPayment(feeId) {
    if (confirm('Are you sure you want to mark this fee as paid?')) {
        window.location.href = `mark_paid.php?id=${feeId}`;
    }
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
