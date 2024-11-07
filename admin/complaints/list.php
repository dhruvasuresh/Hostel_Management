<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Handle filters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$whereClause = [];
$params = [];

if ($status) {
    $whereClause[] = "c.status = ?";
    $params[] = $status;
}
if ($type) {
    $whereClause[] = "c.type = ?";
    $params[] = $type;
}

$where = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";

// Fetch complaints
$query = "
    SELECT c.*, 
           s.first_name as student_fname, s.last_name as student_lname,
           st.first_name as staff_fname, st.last_name as staff_lname
    FROM complaint c
    JOIN student s ON c.student_id = s.student_id
    LEFT JOIN staff st ON c.staff_id = st.staff_id
    $where
    ORDER BY c.logged_on DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Complaint Management</h2>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= $status == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Resolved" <?= $status == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="Maintenance" <?= $type == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        <option value="Cleanliness" <?= $type == 'Cleanliness' ? 'selected' : '' ?>>Cleanliness</option>
                        <option value="Security" <?= $type == 'Security' ? 'selected' : '' ?>>Security</option>
                        <option value="Others" <?= $type == 'Others' ? 'selected' : '' ?>>Others</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Complaints Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Logged On</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($complaints as $complaint): ?>
                        <tr>
                            <td><?= $complaint['complaint_id'] ?></td>
                            <td>
                                <a href="../students/view.php?id=<?= $complaint['student_id'] ?>">
                                    <?= htmlspecialchars($complaint['student_fname'] . ' ' . $complaint['student_lname']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($complaint['type']) ?></td>
                            <td><?= htmlspecialchars(substr($complaint['description'], 0, 50)) ?>...</td>
                            <td><?= date('d-m-Y', strtotime($complaint['logged_on'])) ?></td>
                            <td>
                                <?php if ($complaint['staff_id']): ?>
                                    <a href="../staff/view.php?id=<?= $complaint['staff_id'] ?>">
                                        <?= htmlspecialchars($complaint['staff_fname'] . ' ' . $complaint['staff_lname']) ?>
                                    </a>
                                <?php else: ?>
                                    Not Assigned
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $complaint['status'] == 'Resolved' ? 'success' : 
                                    ($complaint['status'] == 'In Progress' ? 'warning' : 'danger') 
                                ?>">
                                    <?= $complaint['status'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $complaint['complaint_id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="update.php?id=<?= $complaint['complaint_id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="confirmDelete(<?= $complaint['complaint_id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($complaints)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No complaints found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(complaintId) {
    if (confirm('Are you sure you want to delete this complaint?')) {
        window.location.href = `delete.php?id=${complaintId}`;
    }
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
