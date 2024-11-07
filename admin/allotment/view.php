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

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$allotment_id = $_GET['id'];

// Fetch allotment details with related information
$stmt = $pdo->prepare("
    SELECT a.*, 
           s.first_name, s.last_name, s.phone_no,
           r.room_type, r.capacity,
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
    WHERE a.allotment_id = ?
");
$stmt->execute([$allotment_id]);
$allotment = $stmt->fetch();

if (!$allotment) {
    $_SESSION['error'] = "Allotment not found";
    header("Location: list.php");
    exit();
}

// Fetch roommates
$stmt = $pdo->prepare("
    SELECT s.first_name, s.last_name
    FROM allotment a2
    JOIN student s ON a2.student_id = s.student_id
    WHERE a2.room_no = ? 
    AND a2.status = 'Active'
    AND a2.student_id != ?
");
$stmt->execute([$allotment['room_no'], $allotment['student_id']]);
$roommates = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>View Allotment Details</h2>
        <div>
            <?php if ($allotment['fee_status'] !== 'Paid'): ?>
                <a href="edit.php?id=<?= $allotment_id ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button onclick="confirmDelete(<?= $allotment_id ?>)" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            <?php else: ?>
                <button class="btn btn-warning" disabled 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Cannot edit after fee payment">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-danger" disabled 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Cannot delete after fee payment">
                    <i class="fas fa-trash"></i> Delete
                </button>
            <?php endif; ?>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Allotment Information -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Allotment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Allotment ID:</strong> #<?= $allotment['allotment_id'] ?></p>
                            <p><strong>Room Number:</strong> <?= $allotment['room_no'] ?></p>
                            <p><strong>Room Type:</strong> <?= $allotment['room_type'] ?> Class</p>
                            <p><strong>Occupancy:</strong> <?= $allotment['current_occupants'] ?>/<?= $allotment['capacity'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Allotment Date:</strong> <?= date('d-m-Y', strtotime($allotment['allotment_date'])) ?></p>
                            <p><strong>End Date:</strong> <?= date('d-m-Y', strtotime($allotment['end_date'])) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $allotment['status'] == 'Active' ? 'success' : 'secondary' ?>">
                                    <?= $allotment['status'] ?>
                                </span>
                            </p>
                            <p><strong>Fee Status:</strong> 
                                <?= getFeeStatusBadge($allotment['fee_status'], $allotment['room_fees']) ?>
                                <?php if ($allotment['fee_status'] === 'Paid'): ?>
                                    <br><small class="text-muted">Cannot modify allotment after payment</small>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Information -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($allotment['first_name'] . ' ' . $allotment['last_name']) ?></p>
                    <p><strong>Phone:</strong> <?= $allotment['phone_no'] ?></p>
                </div>
            </div>

            <!-- Roommates -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Roommates</h5>
                </div>
                <div class="card-body">
                    <?php if ($roommates): ?>
                        <ul class="list-group">
                            <?php foreach ($roommates as $roommate): ?>
                                <li class="list-group-item">
                                    <?= htmlspecialchars($roommate['first_name'] . ' ' . $roommate['last_name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No roommates currently</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Room Details -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Room Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Room Type:</strong> <?= $allotment['room_type'] ?> Class</p>
                    <p><strong>Capacity:</strong> <?= $allotment['capacity'] ?> persons</p>
                    <p><strong>Current Occupancy:</strong> <?= $allotment['current_occupants'] ?></p>
                    <p><strong>Room Fee:</strong> ₹<?= number_format($allotment['room_fees'], 2) ?></p>
                </div>
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
