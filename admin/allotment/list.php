<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Helper function for fee status badge
function getFeeStatusBadge($status, $amount = null) {
    if ($status === 'Paid') {
        return '<span class="badge bg-success">Paid</span>';
    } else {
        $html = '<span class="badge bg-warning">Pending</span>';
        if ($amount !== null) {
            $html .= '<br>₹' . number_format($amount, 2);
        }
        return $html;
    }
}

// Handle filters
$status = $_GET['status'] ?? '';
$room_type = $_GET['room_type'] ?? '';
$whereClause = [];
$params = [];

if ($status) {
    $whereClause[] = "a.status = ?";
    $params[] = $status;
}
if ($room_type) {
    $whereClause[] = "r.room_type = ?";
    $params[] = $room_type;
}

$where = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";

// Fetch allotments with fee information
$query = "
    SELECT a.*, 
           s.first_name as student_fname, s.last_name as student_lname,
           r.room_type, r.capacity, r.occupancy,
           CASE 
               WHEN r.room_type = 'Single' THEN 60000
               WHEN r.room_type = 'Double' THEN 50000
               WHEN r.room_type = 'Triple' THEN 40000
           END as room_fees,
           (SELECT status 
            FROM fee 
            WHERE student_id = a.student_id 
            AND created_at >= a.created_at 
            ORDER BY created_at DESC 
            LIMIT 1) as fee_status,
           (SELECT COUNT(*) 
            FROM allotment a2 
            WHERE a2.room_no = a.room_no 
            AND a2.status = 'Active') as current_occupants
    FROM allotment a
    JOIN student s ON a.student_id = s.student_id
    JOIN room r ON a.room_no = r.room_no
    $where
    ORDER BY a.allotment_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$allotments = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Room Allotment Management</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Allotment
        </a>
    </div>

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

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="Active" <?= $status == 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <select name="room_type" class="form-control">
                        <option value="">All Room Types</option>
                        <option value="Single" <?= $room_type == 'Single' ? 'selected' : '' ?>>Single (₹60,000)</option>
                        <option value="Double" <?= $room_type == 'Double' ? 'selected' : '' ?>>Double (₹50,000)</option>
                        <option value="Triple" <?= $room_type == 'Triple' ? 'selected' : '' ?>>Triple (₹40,000)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Allotments Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Fee Status</th>
                            <th>Allotment Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allotments as $allotment): ?>
                        <tr>
                            <td><?= $allotment['allotment_id'] ?></td>
                            <td>
                                <a href="../students/view.php?id=<?= $allotment['student_id'] ?>">
                                    <?= htmlspecialchars($allotment['student_fname'] . ' ' . $allotment['student_lname']) ?>
                                </a>
                            </td>
                            <td><?= $allotment['room_no'] ?></td>
                            <td>
                                <?= $allotment['room_type'] ?> Class
                                (<?= $allotment['current_occupants'] ?>/<?= $allotment['capacity'] ?>)
                            </td>
                            <td>
                                <?= getFeeStatusBadge($allotment['fee_status'], $allotment['room_fees']) ?>
                            </td>
                            <td><?= date('d-m-Y', strtotime($allotment['allotment_date'])) ?></td>
                            <td><?= date('d-m-Y', strtotime($allotment['end_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $allotment['status'] == 'Active' ? 'success' : 'secondary' ?>">
                                    <?= $allotment['status'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $allotment['allotment_id'] ?>" 
                                   class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($allotment['fee_status'] !== 'Paid'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="confirmDelete(<?= $allotment['allotment_id'] ?>)"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-danger" disabled 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Cannot delete after fee payment">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($allotments)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No allotments found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});

function confirmDelete(allotmentId) {
    if (confirm('Are you sure you want to delete this allotment? This will also cancel any pending fees.')) {
        window.location.href = `delete.php?id=${allotmentId}`;
    }
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
