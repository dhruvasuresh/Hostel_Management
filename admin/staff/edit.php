<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$staff_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE staff SET 
            first_name = ?, middle_name = ?, last_name = ?,
            position = ?, salary = ?, phone_no = ?, email = ?,
            address = ?, city = ?, state = ?, postal_code = ?
            WHERE staff_id = ?
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['position'],
            $_POST['salary'],
            $_POST['phone_no'],
            $_POST['email'],
            $_POST['address'],
            $_POST['city'],
            $_POST['state'],
            $_POST['postal_code'],
            $staff_id
        ]);

        // Update password if provided
        if (!empty($_POST['new_password'])) {
            $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users SET password = ?
                WHERE user_id = ? AND role = 'staff'
            ");
            $stmt->execute([$hashedPassword, $staff_id]);
        }

        $_SESSION['success'] = "Staff member updated successfully";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating staff member: " . $e->getMessage();
    }
}

// Fetch staff data
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if (!$staff) {
    header("Location: list.php");
    exit();
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Edit Staff Member</h2>

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
                               value="<?= htmlspecialchars($staff['first_name']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" 
                               value="<?= htmlspecialchars($staff['middle_name']) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?= htmlspecialchars($staff['last_name']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Position</label>
                        <select name="position" class="form-control" required>
                            <option value="Warden" <?= $staff['position'] == 'Warden' ? 'selected' : '' ?>>Warden</option>
                            <option value="Assistant Warden" <?= $staff['position'] == 'Assistant Warden' ? 'selected' : '' ?>>Assistant Warden</option>
                            <option value="Security" <?= $staff['position'] == 'Security' ? 'selected' : '' ?>>Security</option>
                            <option value="Maintenance" <?= $staff['position'] == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            <option value="Cleaning" <?= $staff['position'] == 'Cleaning' ? 'selected' : '' ?>>Cleaning</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Salary</label>
                        <input type="number" name="salary" class="form-control" 
                               value="<?= $staff['salary'] ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($staff['email']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Phone Number</label>
                        <input type="text" name="phone_no" class="form-control" 
                               value="<?= htmlspecialchars($staff['phone_no']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control" 
                               value="<?= htmlspecialchars($staff['address']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" 
                               value="<?= htmlspecialchars($staff['city']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>State</label>
                        <input type="text" name="state" class="form-control" 
                               value="<?= htmlspecialchars($staff['state']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" 
                               value="<?= htmlspecialchars($staff['postal_code']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update Staff Member</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
