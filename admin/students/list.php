<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Handle search
$search = $_GET['search'] ?? '';
$whereClause = '';
$params = [];

if ($search) {
    $whereClause = "WHERE first_name LIKE ? OR last_name LIKE ? OR phone_no LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Fetch students
$query = "SELECT s.*, r.room_no, 
          (SELECT SUM(amount) FROM fee WHERE student_id = s.student_id AND status = 'Pending') as pending_fees
          FROM student s 
          LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
          LEFT JOIN room r ON a.room_no = r.room_no 
          $whereClause 
          ORDER BY s.student_id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Student Management</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Student
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name or phone number" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Room</th>
                            <th>Pending Fees</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $student): ?>
                        <tr>
                            <td><?= $student['student_id'] ?></td>
                            <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                            <td><?= htmlspecialchars($student['phone_no']) ?></td>
                            <td><?= $student['room_no'] ?: 'Not Assigned' ?></td>
                            <td>
                                <?php if ($student['pending_fees']): ?>
                                    <span class="text-danger">₹<?= number_format($student['pending_fees'], 2) ?></span>
                                <?php else: ?>
                                    <span class="text-success">No pending fees</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="confirmDelete(<?= $student['student_id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No students found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(studentId) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        window.location.href = `delete.php?id=${studentId}`;
    }
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
