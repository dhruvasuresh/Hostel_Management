<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Insert student
        $stmt = $pdo->prepare("
            INSERT INTO student (
                first_name, middle_name, last_name, date_of_birth, 
                age, phone_no, address, city, state, postal_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            !empty($_POST['middle_name']) ? $_POST['middle_name'] : null,
            !empty($_POST['last_name']) ? $_POST['last_name'] : null,
            $_POST['date_of_birth'],
            $_POST['age'],
            $_POST['phone_no'],
            $_POST['address'],
            $_POST['city'],
            $_POST['state'],
            $_POST['postal_code']
        ]);

        $studentId = $pdo->lastInsertId();

        // Generate username and password
        $username = strtolower($_POST['first_name']);
        $password = date('dmY', strtotime($_POST['date_of_birth']));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Create user account
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, user_id)
            VALUES (?, ?, 'student', ?)
        ");
        $stmt->execute([$username, $hashedPassword, $studentId]);

        $pdo->commit();
        $_SESSION['success'] = "Student added successfully.<br>Login credentials:<br>Username: $username<br>Password: $password (Date of Birth)";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error adding student: " . $e->getMessage();
    }
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Add New Student</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                        <small class="text-muted">This will be used as the login username (in lowercase)</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Middle Name (Optional)</label>
                        <input type="text" name="middle_name" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Last Name (Optional)</label>
                        <input type="text" name="last_name" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" required>
                        <small class="text-muted">This will be used as the login password (DDMMYYYY)</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Age</label>
                        <input type="number" name="age" class="form-control" required readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Phone Number</label>
                        <input type="text" name="phone_no" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>State</label>
                        <input type="text" name="state" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Student</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dobInput = document.querySelector('input[name="date_of_birth"]');
    const ageInput = document.querySelector('input[name="age"]');

    function calculateAge(birthDate) {
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    }

    dobInput.addEventListener('change', function() {
        if(this.value) {
            const age = calculateAge(this.value);
            ageInput.value = age;
        }
    });
});
</script>

<?php include '../../includes/admin_footer.php'; ?>
