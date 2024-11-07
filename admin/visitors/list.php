<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Handle filters
$status = $_GET['status'] ?? '';
$whereClause = [];
$params = [];

if ($status) {
    $whereClause[] = "v.status = ?";
    $params[] = $status;
}

$where = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";

// Fetch visitor requests
$query = "
    SELECT v.*, 
           s.first_name as student_fname, s.last_name as student_lname,
           r.room_no
    FROM visitor v
    JOIN student s ON v.student_id = s.student_id
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    $where
    ORDER BY v.visit_date DESC, v.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$visitors = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Visitor Requests</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $status == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Visitors Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Room</th>
                            <th>Visitor</th>
                            <th>Relation</th>
                            <th>Visit Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($visitors as $visitor): ?>
                        <tr>
                            <td><?= $visitor['visitor_id'] ?></td>
                            <td>
                                <?= htmlspecialchars($visitor['student_fname'] . ' ' . $visitor['student_lname']) ?>
                            </td>
                            <td><?= $visitor['room_no'] ?? 'N/A' ?></td>
                            <td>
                                <?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?>
                                <br>
                                <small class="text-muted"><?= $visitor['phone_no'] ?></small>
                            </td>
                            <td><?= $visitor['relation'] ?></td>
                            <td><?= date('d-m-Y', strtotime($visitor['visit_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($visitor['status']) {
                                        'Approved' => 'success',
                                        'Rejected' => 'danger',
                                        default => 'warning'
                                    };
                                ?>">
                                    <?= $visitor['status'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if($visitor['status'] == 'Pending'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-success" 
                                            onclick="updateStatus(<?= $visitor['visitor_id'] ?>, 'Approved')"
                                            title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="updateStatus(<?= $visitor['visitor_id'] ?>, 'Rejected')"
                                            title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="view.php?id=<?= $visitor['visitor_id'] ?>" 
                                   class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($visitors)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No visitor requests found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(visitorId, status) {
    if (confirm(`Are you sure you want to ${status.toLowerCase()} this visitor request?`)) {
        window.location.href = `update_status.php?id=${visitorId}&status=${status}`;
    }
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
