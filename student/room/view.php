<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkStudent();

// Fetch room details for the logged-in student
$stmt = $pdo->prepare("
    SELECT r.*, a.allotment_date, a.end_date,
           (SELECT COUNT(*) 
            FROM allotment a_count 
            WHERE a_count.room_no = r.room_no 
            AND a_count.status = 'Active') as current_occupants,
           GROUP_CONCAT(DISTINCT CONCAT(s2.first_name, ' ', s2.last_name)) as roommates
    FROM student s
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    LEFT JOIN allotment a2 ON r.room_no = a2.room_no 
        AND a2.status = 'Active' 
        AND a2.student_id != s.student_id
    LEFT JOIN student s2 ON a2.student_id = s2.student_id
    WHERE s.student_id = ?
    GROUP BY r.room_no
");
$stmt->execute([$_SESSION['actual_id']]);
$room = $stmt->fetch();

// Fetch maintenance history
$stmt = $pdo->prepare("
    SELECT c.*, s.first_name as staff_fname, s.last_name as staff_lname
    FROM complaint c
    LEFT JOIN staff s ON c.staff_id = s.staff_id
    WHERE c.student_id = ? AND c.type = 'Maintenance'
    ORDER BY c.logged_on DESC
");
$stmt->execute([$_SESSION['actual_id']]);
$maintenance_history = $stmt->fetchAll();

include '../../includes/student_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">My Room Details</h2>

    <?php if (!$room['room_no']): ?>
        <div class="alert alert-info">
            You currently don't have a room assigned. Please contact the hostel administration.
        </div>
    <?php else: ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Room Information -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary">Room Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Room Number:</strong> <?= $room['room_no'] ?></p>
                            <p><strong>Room Type:</strong> <?= $room['room_type'] ?> Class</p>
                            <p><strong>Capacity:</strong> <?= $room['capacity'] ?> persons</p>
                            <p><strong>Current Occupancy:</strong> 
                                <?= $room['current_occupants'] ?? 0 ?>/<?= $room['capacity'] ?>
                                <?php if ($room['status'] == 'Full'): ?>
                                    <span class="badge bg-warning">Full</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Allotment Date:</strong> <?= date('d-m-Y', strtotime($room['allotment_date'])) ?></p>
                            <p><strong>End Date:</strong> <?= date('d-m-Y', strtotime($room['end_date'])) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $room['status'] == 'Available' ? 'success' : 'warning' ?>">
                                    <?= $room['status'] ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Roommates -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary">Roommates</h5>
                </div>
                <div class="card-body">
                    <?php if ($room['roommates']): ?>
                        <ul class="list-group">
                            <?php foreach(explode(',', $room['roommates']) as $roommate): ?>
                                <li class="list-group-item"><?= htmlspecialchars($roommate) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No roommates currently</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Maintenance History -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary">Maintenance History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Issue</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($maintenance_history as $maintenance): ?>
                                <tr>
                                    <td><?= date('d-m-Y', strtotime($maintenance['logged_on'])) ?></td>
                                    <td><?= htmlspecialchars($maintenance['description']) ?></td>
                                    <td>
                                        <?= $maintenance['staff_fname'] ? 
                                            htmlspecialchars($maintenance['staff_fname'] . ' ' . $maintenance['staff_lname']) : 
                                            'Not Assigned' ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $maintenance['status'] == 'Resolved' ? 'success' : 
                                            ($maintenance['status'] == 'In Progress' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= $maintenance['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($maintenance_history)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No maintenance history found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../complaints/add.php?type=Maintenance" class="btn btn-primary">
                            <i class="fas fa-tools"></i> Report Maintenance Issue
                        </a>
                        <a href="../complaints/add.php?type=Cleanliness" class="btn btn-info">
                            <i class="fas fa-broom"></i> Report Cleanliness Issue
                        </a>
                        <button onclick="printRoomCard()" class="btn btn-success">
                            <i class="fas fa-print"></i> Print Room Card
                        </button>
                    </div>
                </div>
            </div>

            <!-- Room Rules -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary">Room Rules</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item">Keep the room clean and tidy</li>
                        <li class="list-group-item">No loud noise after 10 PM</li>
                        <li class="list-group-item">No unauthorized guests allowed</li>
                        <li class="list-group-item">Report maintenance issues promptly</li>
                        <li class="list-group-item">No modification to room structure</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Room Card Template -->
<div id="roomCard" style="display: none;">
    <div style="padding: 20px; border: 2px solid #000; max-width: 400px; margin: 20px auto;">
        <h3 style="text-align: center;">Room Card</h3>
        <hr>
        <p><strong>Room Number:</strong> <?= $room['room_no'] ?></p>
        <p><strong>Room Type:</strong> <?= $room['room_type'] ?> Class</p>
        <p><strong>Occupant:</strong> <?= $_SESSION['name'] ?></p>
        <p><strong>Valid From:</strong> <?= date('d-m-Y', strtotime($room['allotment_date'])) ?></p>
        <p><strong>Valid Until:</strong> <?= date('d-m-Y', strtotime($room['end_date'])) ?></p>
        <hr>
        <p style="text-align: center; font-size: 12px;">Please keep this card with you at all times</p>
    </div>
</div>

<script>
function printRoomCard() {
    const printContent = document.getElementById('roomCard').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    
    // Reload the page after printing to restore functionality
    location.reload();
}
</script>

<?php include '../../includes/student_footer.php'; ?>
