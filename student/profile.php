<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkStudent();

// Fetch student details
$stmt = $pdo->prepare("
    SELECT s.*, r.room_no, r.room_type
    FROM student s
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    WHERE s.student_id = ?
");
$stmt->execute([$_SESSION['actual_id']]);
$student = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE student SET 
            phone_no = ?, address = ?, city = ?, 
            state = ?, postal_code = ?
            WHERE student_id = ?
        ");
        
        $stmt->execute([
            $_POST['phone_no'],
            $_POST['address'],
            $_POST['city'],
            $_POST['state'],
            $_POST['postal_code'],
            $_SESSION['actual_id']
        ]);

        // Update password if provided
        if (!empty($_POST['new_password'])) {
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                throw new Exception("Passwords do not match");
            }

            $stmt = $pdo->prepare("
                UPDATE users SET password = ?
                WHERE user_id = ? AND role = 'student'
            ");
            $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt->execute([$hashedPassword, $_SESSION['actual_id']]);
        }

        $_SESSION['success'] = "Profile updated successfully";
        header("Location: profile.php");
        exit();
    } catch (Exception $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

include '../includes/student_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">My Profile</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>First Name</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($student['first_name']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Last Name</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($student['last_name']) ?>" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Phone Number</label>
                                <input type="text" name="phone_no" class="form-control" 
                                       value="<?= htmlspecialchars($student['phone_no']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Date of Birth</label>
                                <input type="date" class="form-control" 
                                       value="<?= $student['date_of_birth'] ?>" readonly>
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
                            <div class="col-md-6 mb-3">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Room Information -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5>Room Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($student['room_no']): ?>
                        <p><strong>Room Number:</strong> <?= $student['room_no'] ?></p>
                        <p><strong>Room Type:</strong> <?= $student['room_type'] ?> Class</p>
                    <?php else: ?>
                        <p>No room currently assigned</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/student_footer.php'; ?>
