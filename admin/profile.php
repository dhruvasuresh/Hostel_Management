<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkAdmin();

// Fetch admin details
$stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_id = ?");
$stmt->execute([$_SESSION['actual_id']]);
$admin = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE admin SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone_no = ?
            WHERE admin_id = ?
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone_no'],
            $_SESSION['actual_id']
        ]);

        // Update password if provided
        if (!empty($_POST['new_password'])) {
            $stmt = $pdo->prepare("
                UPDATE users SET 
                password = ? 
                WHERE user_id = ? AND role = 'admin'
            ");
            $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt->execute([$hashedPassword, $_SESSION['actual_id']]);
        }

        $_SESSION['success'] = "Profile updated successfully";
        header("Location: profile.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Admin Profile</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?= htmlspecialchars($admin['first_name']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?= htmlspecialchars($admin['last_name']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($admin['email']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Phone Number</label>
                        <input type="text" name="phone_no" class="form-control" 
                               value="<?= htmlspecialchars($admin['phone_no']) ?>" required>
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

<?php include '../includes/admin_footer.php'; ?>
