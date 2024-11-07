<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO staff (
                first_name, middle_name, last_name, position, salary,
                phone_no, email, address, city, state, postal_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            !empty($_POST['middle_name']) ? $_POST['middle_name'] : null,  // Changed here
            !empty($_POST['last_name']) ? $_POST['last_name'] : null,      // Changed here
            $_POST['position'],
            $_POST['salary'],
            $_POST['phone_no'],
            $_POST['email'],
            $_POST['address'],
            $_POST['city'],
            $_POST['state'],
            $_POST['postal_code']
        ]);

        $_SESSION['success'] = "Staff member added successfully";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding staff member: " . $e->getMessage();
    }
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Add New Staff Member</h2>

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
                    <div class="col-md-6 mb-3">
                        <label>Position</label>
                        <select name="position" class="form-control" required>
                            <option value="">Select Position</option>
                            <option value="Warden">Warden</option>
                            <option value="Assistant Warden">Assistant Warden</option>
                            <option value="Security">Security</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Cleaning">Cleaning</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Salary</label>
                        <input type="number" name="salary" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
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

                <button type="submit" class="btn btn-primary">Add Staff Member</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
