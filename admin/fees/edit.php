<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$fee_id = $_GET['id'];

// Fetch students for dropdown
$stmt = $pdo->query("
    SELECT s.*, r.room_no, r.room_type
    FROM student s
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    ORDER BY s.first_name
");
$students = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE fee SET 
            student_id = ?, amount = ?, due_date = ?,
            status = ?, paid_on = ?
            WHERE fee_id = ?
        ");
        
        $paid_on = $_POST['status'] == 'Paid' ? ($_POST['paid_on'] ?? date('Y-m-d')) : null;
        
        $stmt->execute([
            $_POST['student_id'],
            $_POST['amount'],
            $_POST['due_date'],
            $_POST['status'],
            $paid_on,
            $fee_id
        ]);

        $_SESSION['success'] = "Fee record updated successfully";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating fee record: " . $e->getMessage();
    }
}

// Fetch fee data
$stmt = $pdo->prepare("SELECT * FROM fee WHERE fee_id = ?");
$stmt->execute([$fee_id]);
$fee = $stmt->fetch();

if (!$fee) {
    header("Location: list.php");
    exit();
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Edit Fee Record</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Student</label>
                        <select name="student_id" class="form-control" required>
                            <?php foreach($students as $student): ?>
                                <option value="<?= $student['student_id'] ?>"
                                        <?= $fee['student_id'] == $student['student_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    <?php if ($student['room_no']): ?>
                                        (Room: <?= $student['room_no'] ?> - <?= $student['room_type'] ?> Class)
                                    <?php else: ?>
                                        (No Room Assigned)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Amount (₹)</label>
                        <input type="number" name="amount" class="form-control" 
                               value="<?= $fee['amount'] ?>" step="0.01" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" 
                               value="<?= date('Y-m-d', strtotime($fee['due_date'])) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required 
                                onchange="togglePaidDate(this.value)">
                            <option value="Pending" <?= $fee['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Paid" <?= $fee['status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="Overdue" <?= $fee['status'] == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="paidDateDiv" 
                         style="display: <?= $fee['status'] == 'Paid' ? 'block' : 'none' ?>;">
                        <label>Paid On</label>
                        <input type="date" name="paid_on" class="form-control" 
                               value="<?= $fee['paid_on'] ? date('Y-m-d', strtotime($fee['paid_on'])) : date('Y-m-d') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update Fee</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
function togglePaidDate(status) {
    const paidDateDiv = document.getElementById('paidDateDiv');
    paidDateDiv.style.display = status === 'Paid' ? 'block' : 'none';
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
