<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$room_no = $_GET['id'];

// Fetch room details with current occupants
$stmt = $pdo->prepare("
    SELECT r.*, 
           (SELECT COUNT(*) 
            FROM allotment a 
            WHERE a.room_no = r.room_no 
            AND a.status = 'Active') as current_occupants,
           (SELECT GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ')
            FROM allotment a2 
            JOIN student s ON a2.student_id = s.student_id
            WHERE a2.room_no = r.room_no 
            AND a2.status = 'Active') as student_names
    FROM room r
    WHERE r.room_no = ?
");
$stmt->execute([$room_no]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: list.php");
    exit();
}

// Fetch allotment history
$stmt = $pdo->prepare("
    SELECT a.*, s.first_name, s.last_name
    FROM allotment a
    JOIN student s ON a.student_id = s.student_id
    WHERE a.room_no = ?
    ORDER BY a.allotment_date DESC
");
$stmt->execute([$room_no]);
$allotments = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Room Details</h2>
        <div>
            <a href="edit.php?id=<?= $room['room_no'] ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header">
            <h5 class="m-0">Room Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Room Number:</strong> <?= $room['room_no'] ?></p>
                    <p><strong>Room Type:</strong> <?= $room['room_type'] ?> Class</p>
                    <p><strong>Capacity:</strong> <?= $room['capacity'] ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Current Occupancy:</strong> <?= $room['current_occupants'] ?>/<?= $room['capacity'] ?></p>
                    <p>
                        <strong>Status:</strong> 
                        <span class="badge bg-<?= 
                            $room['status'] == 'Available' ? 'success' : 
                            ($room['status'] == 'Full' ? 'warning' : 'danger') 
                        ?>">
                            <?= $room['status'] ?>
                        </span>
                    </p>
                </div>
            </div>

            <?php if ($room['current_occupants'] > 0 && $room['student_names']): ?>
            <div class="mt-4">
                <h5>Current Occupants:</h5>
                <ul class="list-group">
                    <?php foreach(explode(', ', $room['student_names']) as $student): ?>
                        <li class="list-group-item"><?= htmlspecialchars($student) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Allotment History -->
    <div class="card shadow mt-4">
        <div class="card-header">
            <h5 class="m-0">Allotment History</h5>
        </div>
        <div class="card-body">
            <?php if ($allotments): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>From Date</th>
                            <th>To Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allotments as $allotment): ?>
                        <tr>
                            <td>
                                <a href="../students/view.php?id=<?= $allotment['student_id'] ?>">
                                    <?= htmlspecialchars($allotment['first_name'] . ' ' . $allotment['last_name']) ?>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($allotment['allotment_date'])) ?></td>
                            <td><?= date('d-m-Y', strtotime($allotment['end_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $allotment['status'] == 'Active' ? 'success' : 'secondary' ?>">
                                    <?= $allotment['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted mb-0">No allotment history found</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
