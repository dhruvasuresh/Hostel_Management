<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkStudent();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO visitor (
                student_id, first_name, last_name,
                phone_no, relation, visit_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        
        $stmt->execute([
            $_SESSION['actual_id'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone_no'],
            $_POST['relation'],
            $_POST['visit_date']
        ]);

        $_SESSION['success'] = "Visitor request submitted successfully. Waiting for admin approval.";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error submitting request: " . $e->getMessage();
    }
}

include '../../includes/student_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Request Visitor Pass</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                        <div class="invalid-feedback">Please enter first name</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                        <div class="invalid-feedback">Please enter last name</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Phone Number</label>
                        <input type="tel" name="phone_no" class="form-control" required
                               pattern="[0-9]{10}" maxlength="10">
                        <div class="invalid-feedback">Please enter a valid 10-digit phone number</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Relation</label>
                        <select name="relation" class="form-control" required>
                            <option value="">Select Relation</option>
                            <option value="Parent">Parent</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="invalid-feedback">Please select relation</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Visit Date</label>
                        <input type="date" name="visit_date" class="form-control" required
                               min="<?= date('Y-m-d') ?>">
                        <div class="invalid-feedback">Please select a valid future date</div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Your request will need to be approved by the admin before the visitor is allowed.
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php include '../../includes/student_footer.php'; ?>
