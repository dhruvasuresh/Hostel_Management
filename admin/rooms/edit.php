<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$room_no = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Set capacity based on room type
        switch($_POST['room_type']) {
            case 'Single':
                $capacity = 1;
                break;
            case 'Double':
                $capacity = 2;
                break;
            case 'Triple':
                $capacity = 3;
                break;
            default:
                throw new Exception("Invalid room type");
        }

        $stmt = $pdo->prepare("
            UPDATE room SET 
            room_type = ?,
            capacity = ?,
            status = ?
            WHERE room_no = ?
        ");
        
        $stmt->execute([
            $_POST['room_type'],
            $capacity,
            $_POST['status'],
            $room_no
        ]);

        $_SESSION['success'] = "Room updated successfully";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating room: " . $e->getMessage();
    }
}

// Fetch room data
$stmt = $pdo->prepare("SELECT * FROM room WHERE room_no = ?");
$stmt->execute([$room_no]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: list.php");
    exit();
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Edit Room</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Room Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($room['room_no']) ?>" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Room Type</label>
                        <select name="room_type" class="form-control" required>
                            <option value="Single" <?= $room['room_type'] == 'Single' ? 'selected' : '' ?>>Single Room</option>
                            <option value="Double" <?= $room['room_type'] == 'Double' ? 'selected' : '' ?>>Double Room</option>
                            <option value="Triple" <?= $room['room_type'] == 'Triple' ? 'selected' : '' ?>>Triple Room</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Available" <?= $room['status'] == 'Available' ? 'selected' : '' ?>>Available</option>
                            <option value="Full" <?= $room['status'] == 'Full' ? 'selected' : '' ?>>Full</option>
                            <option value="Maintenance" <?= $room['status'] == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Update Room</button>
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
