<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkStudent();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$complaint_id = $_GET['id'];

// Fetch complaint details
$stmt = $pdo->prepare("
    SELECT c.*, s.first_name as staff_fname, s.last_name as staff_lname
    FROM complaint c
    LEFT JOIN staff s ON c.staff_id = s.staff_id
    WHERE c.complaint_id = ? AND c.student_id = ?
");
$stmt->execute([$complaint_id, $_SESSION['actual_id']]);
$complaint = $stmt->fetch();

if (!$complaint) {
    header("Location: list.php");
    exit();
}

include '../../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Complaint Details</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Complaint ID:</strong> #<?= $complaint['complaint_id'] ?></p>
                    <p><strong>Type:</strong> <?= htmlspecialchars($complaint['type']) ?></p>
                    <p><strong>Date Logged:</strong> <?= date('d-m-Y', strtotime($complaint['logged_on'])) ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= 
                            $complaint['status'] == 'Resolved' ? 'success' : 
                            ($complaint['status'] == 'In Progress' ? 'warning' : 'danger') 
                        ?>">
                            <?= $complaint['status'] ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Assigned To:</strong> 
                        <?= $complaint['staff_fname'] ? 
                            htmlspecialchars($complaint['staff_fname'] . ' ' . $complaint['staff_lname']) : 
                            'Not Assigned' ?>
                    </p>
                    <?php if ($complaint['resolved_on']): ?>
                        <p><strong>Resolved On:</strong> <?= date('d-m-Y', strtotime($complaint['resolved_on'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h5>Description:</h5>
                    <p><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/student_footer.php'; ?>
