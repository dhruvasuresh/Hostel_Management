<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkStudent();

// Fetch all fees for the logged-in student with room details
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
    WHERE f.student_id = ?
    ORDER BY f.due_date DESC
");
$stmt->execute([$_SESSION['actual_id']]);
$fees = $stmt->fetchAll();

// Calculate totals
$total_pending = 0;
$total_paid = 0;
foreach($fees as $fee) {
    if($fee['status'] == 'Pending') {
        $total_pending += $fee['amount'];
    } elseif($fee['status'] == 'Paid') {
        $total_paid += $fee['amount'];
    }
}

include '../../includes/student_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">My Fees</h2>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Pending Fees</h5>
                    <h3>₹<?= number_format($total_pending, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Paid</h5>
                    <h3>₹<?= number_format($total_paid, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Current Room Fee</h5>
                    <h3>₹<?= $fees[0]['room_fees'] ?? 'N/A' ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Records -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Room</th>
                            <th>Room Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Paid On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fees as $fee): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($fee['due_date'])) ?></td>
                            <td><?= $fee['room_no'] ?? 'N/A' ?></td>
                            <td>
                                <?php if($fee['room_type']): ?>
                                    <?= $fee['room_type'] ?> Class
                                    <br>
                                    <small class="text-muted">
                                        (₹<?= $fee['room_fees'] ?> per semester)
                                    </small>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>₹<?= number_format($fee['amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $fee['status'] == 'Paid' ? 'success' : 
                                    ($fee['status'] == 'Pending' ? 'warning' : 'danger') 
                                ?>">
                                    <?= $fee['status'] ?>
                                </span>
                            </td>
                            <td><?= $fee['paid_on'] ? date('d-m-Y', strtotime($fee['paid_on'])) : '-' ?></td>
                            <td>
                                <a href="view.php?id=<?= $fee['fee_id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fees)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No fee records found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/student_footer.php'; ?>
