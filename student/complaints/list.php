<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkStudent();

// Fetch complaints for the logged-in student
$stmt = $pdo->prepare("
    SELECT c.*, s.first_name as staff_fname, s.last_name as staff_lname
    FROM complaint c
    LEFT JOIN staff s ON c.staff_id = s.staff_id
    WHERE c.student_id = ?
    ORDER BY c.logged_on DESC
");
$stmt->execute([$_SESSION['actual_id']]);
$complaints = $stmt->fetchAll();

include '../../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Complaints</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Register New Complaint
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($complaints as $complaint): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($complaint['logged_on'])) ?></td>
                            <td><?= htmlspecialchars($complaint['type']) ?></td>
                            <td><?= htmlspecialchars(substr($complaint['description'], 0, 50)) ?>...</td>
                            <td>
                                <span class="badge bg-<?= 
                                    $complaint['status'] == 'Resolved' ? 'success' : 
                                    ($complaint['status'] == 'In Progress' ? 'warning' : 'danger') 
                                ?>">
                                    <?= $complaint['status'] ?>
                                </span>
                            </td>
                            <td>
                                <?= $complaint['staff_fname'] ? 
                                    htmlspecialchars($complaint['staff_fname'] . ' ' . $complaint['staff_lname']) : 
                                    'Not Assigned' ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $complaint['complaint_id'] ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($complaints)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No complaints found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/student_footer.php'; ?>
