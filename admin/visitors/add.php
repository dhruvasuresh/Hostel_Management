<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Fetch students for dropdown
$stmt = $pdo->query("
    SELECT s.*, r.room_no 
    FROM student s
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    ORDER BY s.first_name
");
$students = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO visitor (
                student_id, first_name, middle_name, last_name,
                phone_no, relation, visit_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['student_id'],
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['phone_no'],
            $_POST['relation'],
            $_POST['visit_date']
        ]);

        $_SESSION['success'] = "Visitor record added successfully";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding visitor record: " . $e->getMessage();
    }
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Add New Visitor</h2>

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
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Phone Number</label>
                        <input type="text" name="phone_no" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Relation with Student</label>
                        <select name="relation" class="form-control" required>
                            <option value="">Select Relation</option>
                            <option value="Parent">Parent</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Visit Date</label>
                        <input type="date" name="visit_date" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Student to Visit</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Select Student</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?= $student['student_id'] ?>">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    <?= $student['room_no'] ? ' (Room: ' . $student['room_no'] . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Visitor</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
