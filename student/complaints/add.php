<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkStudent();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO complaint (
                student_id, type, description, logged_on, status
            ) VALUES (?, ?, ?, CURRENT_DATE, 'Pending')
        ");
        
        $stmt->execute([
            $_SESSION['actual_id'],
            $_POST['type'],
            $_POST['description']
        ]);

        $_SESSION['success'] = "Complaint registered successfully";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error registering complaint: " . $e->getMessage();
    }
}

include '../../includes/student_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Register New Complaint</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Complaint Type</label>
                        <select name="type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Cleanliness">Cleanliness</option>
                            <option value="Security">Security</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                        <small class="text-muted">Please provide detailed information about your complaint</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Submit Complaint</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/student_footer.php'; ?>
