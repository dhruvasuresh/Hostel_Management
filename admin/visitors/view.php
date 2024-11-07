<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$visitor_id = $_GET['id'];

// Fetch visitor details with student and room information
$stmt = $pdo->prepare("
    SELECT v.*, 
           s.first_name as student_fname, s.last_name as student_lname,
           r.room_no
    FROM visitor v
    JOIN student s ON v.student_id = s.student_id
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    WHERE v.visitor_id = ?
");
$stmt->execute([$visitor_id]);
$visitor = $stmt->fetch();

if (!$visitor) {
    header("Location: list.php");
    exit();
}

// Fetch previous visits by this visitor (based on phone number)
$stmt = $pdo->prepare("
    SELECT v.*, s.first_name as student_fname, s.last_name as student_lname
    FROM visitor v
    JOIN student s ON v.student_id = s.student_id
    WHERE v.phone_no = ? AND v.visitor_id != ?
    ORDER BY v.visit_date DESC
");
$stmt->execute([$visitor['phone_no'], $visitor_id]);
$previous_visits = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Visitor Details</h2>
        <div>
            <a href="edit.php?id=<?= $visitor_id ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Visitor Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Visitor Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> 
                                <?= htmlspecialchars($visitor['first_name'] . ' ' . 
                                    ($visitor['middle_name'] ? $visitor['middle_name'] . ' ' : '') . 
                                    $visitor['last_name']) ?>
                            </p>
                            <p><strong>Phone Number:</strong> <?= htmlspecialchars($visitor['phone_no']) ?></p>
                            <p><strong>Relation:</strong> <?= htmlspecialchars($visitor['relation']) ?></p>
                            <p><strong>Visit Date:</strong> <?= date('d-m-Y', strtotime($visitor['visit_date'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Student Visited:</strong> 
                                <a href="../students/view.php?id=<?= $visitor['student_id'] ?>">
                                    <?= htmlspecialchars($visitor['student_fname'] . ' ' . $visitor['student_lname']) ?>
                                </a>
                            </p>
                            <p><strong>Student's Room:</strong> <?= $visitor['room_no'] ?: 'Not Assigned' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Previous Visits -->
            <?php if ($previous_visits): ?>
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Previous Visits</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student Visited</th>
                                    <th>Relation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($previous_visits as $visit): ?>
                                <tr>
                                    <td><?= date('d-m-Y', strtotime($visit['visit_date'])) ?></td>
                                    <td>
                                        <a href="../students/view.php?id=<?= $visit['student_id'] ?>">
                                            <?= htmlspecialchars($visit['student_fname'] . ' ' . $visit['student_lname']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($visit['relation']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="add.php?phone=<?= urlencode($visitor['phone_no']) ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Record New Visit
                        </a>
                        <button onclick="printVisitorPass()" class="btn btn-success">
                            <i class="fas fa-print"></i> Print Visitor Pass
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Visitor Pass Template -->
<div id="visitorPass" style="display: none;">
    <div style="padding: 20px; border: 2px solid #000; max-width: 400px; margin: 20px auto;">
        <h3 style="text-align: center;">Visitor Pass</h3>
        <hr>
        <p><strong>Visitor Name:</strong> <?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($visitor['phone_no']) ?></p>
        <p><strong>Student Name:</strong> <?= htmlspecialchars($visitor['student_fname'] . ' ' . $visitor['student_lname']) ?></p>
        <p><strong>Room Number:</strong> <?= $visitor['room_no'] ?: 'Not Assigned' ?></p>
        <p><strong>Visit Date:</strong> <?= date('d-m-Y', strtotime($visitor['visit_date'])) ?></p>
        <p><strong>Relation:</strong> <?= htmlspecialchars($visitor['relation']) ?></p>
        <hr>
        <p style="text-align: center; font-size: 12px;">Valid only for <?= date('d-m-Y', strtotime($visitor['visit_date'])) ?></p>
    </div>
</div>

<script>
function printVisitorPass() {
    const printContent = document.getElementById('visitorPass').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
