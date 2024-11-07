<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkAdmin();

// Function to handle status badges
function getStatusBadge($status) {
    return match($status) {
        'Resolved' => '<span class="badge bg-success">Resolved</span>',
        'In Progress' => '<span class="badge bg-warning">In Progress</span>',
        'Pending' => '<span class="badge bg-danger">Pending</span>',
        default => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>'
    };
}

// Fetch summary counts
$stmt = $pdo->query("SELECT COUNT(*) as count FROM student");
$studentCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM room");
$roomCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM complaint WHERE status != 'Resolved'");
$activeComplaints = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT SUM(amount) as total FROM fee WHERE status = 'Pending'");
$pendingFees = $stmt->fetch()['total'] ?? 0;

include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Admin Dashboard</h2>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $studentCount ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Rooms</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $roomCount ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-door-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Complaints</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $activeComplaints ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pending Fees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?= number_format($pendingFees, 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <!-- Recent Complaints -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Complaints</h6>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("
                        SELECT c.*, s.first_name, s.last_name 
                        FROM complaint c
                        JOIN student s ON c.student_id = s.student_id
                        ORDER BY c.logged_on DESC LIMIT 5
                    ");
                    $complaints = $stmt->fetchAll();
                    
                    if ($complaints): ?>
                        <div class="list-group">
                            <?php foreach ($complaints as $complaint): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($complaint['first_name'] . ' ' . $complaint['last_name']) ?></h6>
                                        <small><?= date('d M Y', strtotime($complaint['logged_on'])) ?></small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($complaint['description']) ?></p>
                                    <small>Status: 
                                        <span class="badge bg-<?php 
                                            echo match($complaint['status']) {
                                                'Resolved' => 'success',
                                                'In Progress' => 'warning',
                                                'Pending' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?= htmlspecialchars($complaint['status']) ?>
                                        </span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No complaints found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Fees -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Fee Payments</h6>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("
                        SELECT f.*, s.first_name, s.last_name 
                        FROM fee f
                        JOIN student s ON f.student_id = s.student_id
                        WHERE f.status = 'Paid'
                        ORDER BY f.paid_on DESC LIMIT 5
                    ");
                    $fees = $stmt->fetchAll();
                    
                    if ($fees): ?>
                        <div class="list-group">
                            <?php foreach ($fees as $fee): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?></h6>
                                        <small><?= date('d M Y', strtotime($fee['paid_on'])) ?></small>
                                    </div>
                                    <p class="mb-1">Amount: ₹<?= number_format($fee['amount'], 2) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No recent payments found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
