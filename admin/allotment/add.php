<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Fetch students without active allotments
$stmt = $pdo->query("CALL sp_get_available_students()");
$students = $stmt->fetchAll();
$stmt->closeCursor(); // Important when calling multiple stored procedures

// Fetch available rooms
$stmt = $pdo->query("CALL sp_get_available_rooms()");
$rooms = $stmt->fetchAll();
$stmt->closeCursor();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Check if room is still available
        $stmt = $pdo->prepare("CALL sp_check_room_availability(?)");
        $stmt->execute([$_POST['room_no']]);
        $room = $stmt->fetch();
        $stmt->closeCursor();

        if (!$room) {
            throw new Exception("Room is no longer available");
        }

        // Create allotment with fee
        $stmt = $pdo->prepare("CALL sp_create_allotment(?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['student_id'],
            $_POST['room_no'],
            $_POST['allotment_date'],
            $_POST['end_date'],
            $room['fees']
        ]);
        $stmt->closeCursor();

        $_SESSION['success'] = "Room allotted and fee record created successfully";
        header("Location: list.php");
        exit();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">New Room Allotment</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if (empty($students)): ?>
        <div class="alert alert-warning">No students available for allotment.</div>
    <?php elseif (empty($rooms)): ?>
        <div class="alert alert-warning">No rooms available for allotment.</div>
    <?php else: ?>
        <div class="card shadow">
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Student</label>
                            <select name="student_id" class="form-control" required>
                                <option value="">Select Student</option>
                                <?php foreach($students as $student): ?>
                                    <option value="<?= $student['student_id'] ?>">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Room</label>
                            <select name="room_no" class="form-control" required>
                                <option value="">Select Room</option>
                                <?php foreach($rooms as $room): ?>
                                    <option value="<?= $room['room_no'] ?>">
                                        Room <?= $room['room_no'] ?> 
                                        (<?= $room['room_type'] ?> Class - 
                                        <?= $room['occupancy'] ?>/<?= $room['capacity'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Allotment Date</label>
                            <input type="date" name="allotment_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control"
                                   value="<?= date('Y-m-d', strtotime('+6 months')) ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Allotment</button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/admin_footer.php'; ?>
