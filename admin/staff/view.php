<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$staff_id = $_GET['id'];

// Fetch staff details
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if (!$staff) {
    header("Location: list.php");
    exit();
}

// Fetch complaints handled by this staff member
$stmt = $pdo->prepare("
    SELECT c.*, s.first_name as student_fname, s.last_name as student_lname
    FROM complaint c
    JOIN student s ON c.student_id = s.student_id
    WHERE c.staff_id = ?
    ORDER BY c.logged_on DESC
");
$stmt->execute([$staff_id]);
$complaints = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Staff Member Details</h2>
        <div>
            <a href="edit.php?id=<?= $staff_id ?>" class="btn btn-warning">
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
                            <p><strong>Name:</strong> 
                                <?= htmlspecialchars($staff['first_name'] . ' ' . 
                                    ($staff['middle_name'] ? $staff['middle_name'] . ' ' : '') . 
                                    $staff['last_name']) ?>
                            </p>
                            <p><strong>Position:</strong> <?= htmlspecialchars($staff['position']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($staff['phone_no']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Address:</strong> <?= htmlspecialchars($staff['address']) ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($staff['city']) ?></p>
                            <p><strong>State:</strong> <?= htmlspecialchars($staff['state']) ?></p>
                            <p><strong>Postal Code:</strong> <?= htmlspecialchars($staff['postal_code']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Employment Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Staff ID:</strong> <?= $staff['staff_id'] ?></p>
                            <p><strong>Position:</strong> <?= htmlspecialchars($staff['position']) ?></p>
                            <p><strong>Salary:</strong> ₹<?= number_format($staff['salary'], 2) ?></p>
                            <p><strong>Joined Date:</strong> <?= date('d-m-Y', strtotime($staff['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Quick Stats -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Statistics</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Count total complaints handled
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM complaint WHERE staff_id = ?");
                    $stmt->execute([$staff_id]);
                    $totalComplaints = $stmt->fetchColumn();

                    // Count resolved complaints
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM complaint WHERE staff_id = ? AND status = 'Resolved'");
                    $stmt->execute([$staff_id]);
                    $resolvedComplaints = $stmt->fetchColumn();
                    ?>
                    <div class="mb-3">
                        <h4><?= $totalComplaints ?></h4>
                        <p class="text-muted">Total Complaints Handled</p>
                    </div>
                    <div>
                        <h4><?= $resolvedComplaints ?></h4>
                        <p class="text-muted">Complaints Resolved</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complaints Handled -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Complaints Handled</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($complaints as $complaint): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($complaint['logged_on'])) ?></td>
                            <td>
                                <a href="../students/view.php?id=<?= $complaint['student_id'] ?>">
                                    <?= htmlspecialchars($complaint['student_fname'] . ' ' . $complaint['student_lname']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($complaint['type']) ?></td>
                            <td><?= htmlspecialchars($complaint['description']) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $complaint['status'] == 'Resolved' ? 'success' : 
                                    ($complaint['status'] == 'In Progress' ? 'warning' : 'danger') 
                                ?>">
                                    <?= $complaint['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($complaints)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No complaints handled yet</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
