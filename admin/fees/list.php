<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Get filters from request
$status = $_GET['status'] ?? '';

// Call the stored procedure
$stmt = $pdo->prepare("CALL sp_get_fees(?)");
$stmt->execute([$status]);
$fees = $stmt->fetchAll();
$stmt->closeCursor();

// Get totals using the function
$stmt = $pdo->prepare("SELECT get_total_fees_by_status('Pending') as total_pending, 
                             get_total_fees_by_status('Paid') as total_paid");
$stmt->execute();
$totals = $stmt->fetch();
$stmt->closeCursor();

$total_pending = $totals['total_pending'];
$total_paid = $totals['total_paid'];

include '../../includes/admin_header.php';
?>

<!-- Rest of your HTML code remains the same -->



<div class="container-fluid">
    <h2 class="mb-4">Fee Management</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Total Pending Fees</h5>
                    <h3>₹<?= number_format($total_pending, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Collected Fees</h5>
                    <h3>₹<?= number_format($total_paid, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Paid" <?= $status == 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Overdue" <?= $status == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Fees Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Room</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Paid On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fees as $fee): ?>
                        <tr>
                            <td><?= $fee['fee_id'] ?></td>
                            <td>
                                <a href="../students/view.php?id=<?= $fee['student_id'] ?>">
                                    <?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($fee['room_no']): ?>
                                    Room <?= $fee['room_no'] ?> 
                                    (<?= $fee['room_type'] ?> Class - ₹<?= number_format($fee['room_fees'], 2) ?>)
                                <?php else: ?>
                                    No Room Assigned
                                <?php endif; ?>
                            </td>
                            <td>₹<?= number_format($fee['amount'], 2) ?></td>
                            <td><?= date('d-m-Y', strtotime($fee['due_date'])) ?></td>
                            <td>
                                <?= $fee['paid_on'] ? date('d-m-Y', strtotime($fee['paid_on'])) : '-' ?>
                            </td>
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
                            <td>
                                <a href="view.php?id=<?= $fee['fee_id'] ?>" 
                                   class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($fee['status'] == 'Pending'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-success" 
                                            onclick="confirmPayment(<?= $fee['fee_id'] ?>)"
                                            title="Mark as Paid">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="confirmDelete(<?= $fee['fee_id'] ?>)"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fees)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No fee records found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

function confirmDelete(feeId) {
    if (confirm('Are you sure you want to delete this fee record?')) {
        window.location.href = `delete.php?id=${feeId}`;
    }
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
