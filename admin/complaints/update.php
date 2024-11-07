<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = $_POST['complaint_id'] ?? $_GET['id'];
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'assign':
                    // Assign staff
                    $stmt = $pdo->prepare("
                        UPDATE complaint 
                        SET staff_id = ?, status = 'In Progress'
                        WHERE complaint_id = ?
                    ");
                    $stmt->execute([$_POST['staff_id'], $complaint_id]);
                    $_SESSION['success'] = "Staff assigned successfully";
                    break;

                case 'resolve':
                    // Mark as resolved
                    $stmt = $pdo->prepare("
                        UPDATE complaint 
                        SET status = 'Resolved', resolved_on = CURRENT_DATE
                        WHERE complaint_id = ?
                    ");
                    $stmt->execute([$complaint_id]);
                    $_SESSION['success'] = "Complaint marked as resolved";
                    break;

                default:
                    // Update status and staff
                    $stmt = $pdo->prepare("
                        UPDATE complaint 
                        SET status = ?, staff_id = ?,
                        resolved_on = ? 
                        WHERE complaint_id = ?
                    ");
                    $resolved_on = $_POST['status'] == 'Resolved' ? date('Y-m-d') : null;
                    $stmt->execute([
                        $_POST['status'],
                        $_POST['staff_id'],
                        $resolved_on,
                        $complaint_id
                    ]);
                    $_SESSION['success'] = "Complaint updated successfully";
            }
        }
        
        header("Location: view.php?id=" . $complaint_id);
        exit();
    } catch (PDOException $e) {
        $error = "Error updating complaint: " . $e->getMessage();
    }
}

// If GET request, show update form
if (isset($_GET['id'])) {
    $complaint_id = $_GET['id'];
    
    // Fetch complaint details
    $stmt = $pdo->prepare("
        SELECT c.*, 
               s.first_name as student_fname, s.last_name as student_lname
        FROM complaint c
        JOIN student s ON c.student_id = s.student_id
        WHERE c.complaint_id = ?
    ");
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        header("Location: list.php");
        exit();
    }

    // Fetch staff members
    $stmt = $pdo->query("SELECT staff_id, first_name, last_name FROM staff ORDER BY first_name");
    $staff = $stmt->fetchAll();

    include '../../includes/admin_header.php';
    ?>

    <div class="container-fluid">
        <h2 class="mb-4">Update Complaint Status</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST" action="update.php">
                    <input type="hidden" name="complaint_id" value="<?= $complaint_id ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Student</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($complaint['student_fname'] . ' ' . $complaint['student_lname']) ?>" 
                                   readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Type</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($complaint['type']) ?>" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Pending" <?= $complaint['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= $complaint['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Resolved" <?= $complaint['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Assign To Staff</label>
                            <select name="staff_id" class="form-control" required>
                                <option value="">Select Staff Member</option>
                                <?php foreach($staff as $member): ?>
                                    <option value="<?= $member['staff_id'] ?>" 
                                            <?= $complaint['staff_id'] == $member['staff_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label>Description</label>
                            <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($complaint['description']) ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Complaint</button>
                    <a href="view.php?id=<?= $complaint_id ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/admin_footer.php';
}
?>
