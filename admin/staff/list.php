<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Handle search
$search = $_GET['search'] ?? '';
$whereClause = '';
$params = [];

if ($search) {
    $whereClause = "WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone_no LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

// Fetch staff members
$query = "SELECT * FROM staff $whereClause ORDER BY staff_id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$staff = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Staff Management</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Staff
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name, email or phone" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Salary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($staff as $member): ?>
                        <tr>
                            <td><?= $member['staff_id'] ?></td>
                            <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
                            <td><?= htmlspecialchars($member['position']) ?></td>
                            <td><?= htmlspecialchars($member['email']) ?></td>
                            <td><?= htmlspecialchars($member['phone_no']) ?></td>
                            <td>₹<?= number_format($member['salary'], 2) ?></td>
                            <td>
                                <a href="view.php?id=<?= $member['staff_id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $member['staff_id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="confirmDelete(<?= $member['staff_id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($staff)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No staff members found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(staffId) {
    if (confirm('Are you sure you want to delete this staff member?')) {
        window.location.href = `delete.php?id=${staffId}`;
    }
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
