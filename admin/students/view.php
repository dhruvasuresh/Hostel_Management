<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$student_id = $_GET['id'];

// Fetch student details with room information
$stmt = $pdo->prepare("
    SELECT s.*, r.room_no, r.room_type,
    (SELECT SUM(amount) FROM fee WHERE student_id = s.student_id AND status = 'Pending') as pending_fees
    FROM student s
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    WHERE s.student_id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: list.php");
    exit();
}

// Fetch complaints
$stmt = $pdo->prepare("
    SELECT * FROM complaint 
    WHERE student_id = ? 
    ORDER BY logged_on DESC
");
$stmt->execute([$student_id]);
$complaints = $stmt->fetchAll();

// Fetch fee history
$stmt = $pdo->prepare("
    SELECT * FROM fee 
    WHERE student_id = ? 
    ORDER BY due_date DESC
");
$stmt->execute([$student_id]);
$fees = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Student Details</h2>
        <div>
            <a href="edit.php?id=<?= $student_id ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Personal Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Personal Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']) ?></p>
                            <p><strong>Date of Birth:</strong> <?= date('d-m-Y', strtotime($student['date_of_birth'])) ?></p>
                            <p><strong>Age:</strong> <?= $student['age'] ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($student['phone_no']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Address:</strong> <?= htmlspecialchars($student['address']) ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($student['city']) ?></p>
                            <p><strong>State:</strong> <?= htmlspecialchars($student['state']) ?></p>
                            <p><strong>Postal Code:</strong> <?= htmlspecialchars($student['postal_code']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Room Information</h6>
                </div>
                <div class="card-body">
                    <?php if ($student['room_no']): ?>
                        <p><strong>Room Number:</strong> <?= htmlspecialchars($student['room_no']) ?></p>
                        <p><strong>Room Type:</strong> <?= htmlspecialchars($student['room_type']) ?></p>
                    <?php else: ?>
                        <p>No room currently assigned</p>
                        <a href="../allotment/add.php?student_id=<?= $student_id ?>" class="btn btn-primary">
                            <i class="fas fa-bed"></i> Assign Room
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Fee Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Fee Summary</h6>
                </div>
                <div class="card-body">
                    <h3 class="<?= $student['pending_fees'] > 0 ? 'text-danger' : 'text-success' ?>">
                        ₹<?= number_format($student['pending_fees'] ?: 0, 2) ?>
                    </h3>
                    <p>Pending Fees</p>
                    
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../complaints/list.php?student_id=<?= $student_id ?>" class="btn btn-info">
                            <i class="fas fa-exclamation-circle"></i> View Complaints
                        </a>
                        <a href="../visitors/list.php?student_id=<?= $student_id ?>" class="btn btn-success">
                            <i class="fas fa-users"></i> View Visitors
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee History -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Fee History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Paid On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fees as $fee): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($fee['due_date'])) ?></td>
                            <td>₹<?= number_format($fee['amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $fee['status'] == 'Paid' ? 'success' : 'warning' ?>">
                                    <?= $fee['status'] ?>
                                </span>
                            </td>
                            <td><?= $fee['paid_on'] ? date('d-m-Y', strtotime($fee['paid_on'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Complaints History -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Complaints History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($complaints as $complaint): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($complaint['logged_on'])) ?></td>
                            <td><?= htmlspecialchars($complaint['type']) ?></td>
                            <td><?= htmlspecialchars($complaint['description']) ?></td>
                            <td><?= getStatusBadge($complaint['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
