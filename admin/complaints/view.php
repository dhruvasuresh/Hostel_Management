<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$complaint_id = $_GET['id'];

// Fetch complaint details
$stmt = $pdo->prepare("
    SELECT c.*, 
           s.first_name as student_fname, s.last_name as student_lname,
           st.first_name as staff_fname, st.last_name as staff_lname,
           r.room_no
    FROM complaint c
    JOIN student s ON c.student_id = s.student_id
    LEFT JOIN staff st ON c.staff_id = st.staff_id
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    WHERE c.complaint_id = ?
");
$stmt->execute([$complaint_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    header("Location: list.php");
    exit();
}

// Fetch available staff for assignment
$stmt = $pdo->query("SELECT staff_id, first_name, last_name FROM staff ORDER BY first_name");
$staff = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Complaint Details</h2>
        <div>
            <a href="update.php?id=<?= $complaint_id ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Update Status
            </a>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Complaint Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Complaint Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Complaint ID:</strong> #<?= $complaint['complaint_id'] ?></p>
                            <p><strong>Type:</strong> <?= htmlspecialchars($complaint['type']) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= 
                                    $complaint['status'] == 'Resolved' ? 'success' : 
                                    ($complaint['status'] == 'In Progress' ? 'warning' : 'danger') 
                                ?>">
                                    <?= $complaint['status'] ?>
                                </span>
                            </p>
                            <p><strong>Logged On:</strong> <?= date('d-m-Y', strtotime($complaint['logged_on'])) ?></p>
                            <?php if ($complaint['resolved_on']): ?>
                                <p><strong>Resolved On:</strong> <?= date('d-m-Y', strtotime($complaint['resolved_on'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Student:</strong> 
                                <a href="../students/view.php?id=<?= $complaint['student_id'] ?>">
                                    <?= htmlspecialchars($complaint['student_fname'] . ' ' . $complaint['student_lname']) ?>
                                </a>
                            </p>
                            <p><strong>Room:</strong> <?= $complaint['room_no'] ?: 'Not Assigned' ?></p>
                            <p><strong>Assigned To:</strong> 
                                <?php if ($complaint['staff_id']): ?>
                                    <a href="../staff/view.php?id=<?= $complaint['staff_id'] ?>">
                                        <?= htmlspecialchars($complaint['staff_fname'] . ' ' . $complaint['staff_lname']) ?>
                                    </a>
                                <?php else: ?>
                                    Not Assigned
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <p><strong>Description:</strong></p>
                            <p><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <?php if ($complaint['status'] != 'Resolved'): ?>
                        <form action="update.php" method="POST" class="mb-3">
                            <input type="hidden" name="complaint_id" value="<?= $complaint_id ?>">
                            <div class="mb-3">
                                <label>Assign To Staff</label>
                                <select name="staff_id" class="form-control" required>
                                    <option value="">Select Staff Member</option>
                                    <?php foreach($staff as $member): ?>
                                        <option value="<?= $member['staff_id'] ?>" 
                                                <?= $complaint['staff_id'] == $member['staff_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="action" value="assign" class="btn btn-primary w-100 mb-2">
                                Assign Staff
                            </button>
                        </form>
                        <form action="update.php" method="POST">
                            <input type="hidden" name="complaint_id" value="<?= $complaint_id ?>">
                            <button type="submit" name="action" value="resolve" class="btn btn-success w-100">
                                Mark as Resolved
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success">
                            This complaint has been resolved.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
