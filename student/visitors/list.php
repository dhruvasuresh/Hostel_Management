<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkStudent();

// Fetch visitor requests for the logged-in student
$stmt = $pdo->prepare("
    SELECT * FROM visitor 
    WHERE student_id = ? 
    ORDER BY visit_date DESC
");
$stmt->execute([$_SESSION['actual_id']]);
$visitors = $stmt->fetchAll();

include '../../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Visitor Requests</h2>
        <a href="request.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Request
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
                <table class="table">
                    <thead>
                        <tr>
                            <th>Visitor Name</th>
                            <th>Relation</th>
                            <th>Phone</th>
                            <th>Visit Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($visitors as $visitor): ?>
                            <tr>
                                <td><?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?></td>
                                <td><?= htmlspecialchars($visitor['relation']) ?></td>
                                <td><?= htmlspecialchars($visitor['phone_no']) ?></td>
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
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($visitors)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No visitor requests found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/student_footer.php'; ?>
