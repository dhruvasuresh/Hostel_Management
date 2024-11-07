<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$student_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE student SET 
            first_name = ?,
            middle_name = ?,
            last_name = ?,
            date_of_birth = ?,
            age = ?,
            phone_no = ?,
            address = ?,
            city = ?,
            state = ?,
            postal_code = ?
            WHERE student_id = ?
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['date_of_birth'],
            $_POST['age'],
            $_POST['phone_no'],
            $_POST['address'],
            $_POST['city'],
            $_POST['state'],
            $_POST['postal_code'],
            $student_id
        ]);

        // Update password if provided
        if (!empty($_POST['new_password'])) {
            $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users SET password = ?
                WHERE user_id = ? AND role = 'student'
            ");
            $stmt->execute([$hashedPassword, $student_id]);
        }

        $_SESSION['success'] = "Student updated successfully";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating student: " . $e->getMessage();
    }
}

// Fetch student data
$stmt = $pdo->prepare("SELECT * FROM student WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: list.php");
    exit();
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Edit Student</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?= htmlspecialchars($student['first_name']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" 
                               value="<?= htmlspecialchars($student['middle_name']) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?= htmlspecialchars($student['last_name']) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" 
                               value="<?= $student['date_of_birth'] ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Age</label>
                        <input type="number" name="age" class="form-control" 
                               value="<?= $student['age'] ?>" required readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Phone Number</label>
                        <input type="text" name="phone_no" class="form-control" 
                               value="<?= htmlspecialchars($student['phone_no']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control" 
                               value="<?= htmlspecialchars($student['address']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" 
                               value="<?= htmlspecialchars($student['city']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>State</label>
                        <input type="text" name="state" class="form-control" 
                               value="<?= htmlspecialchars($student['state']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" 
                               value="<?= htmlspecialchars($student['postal_code']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update Student</button>
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
