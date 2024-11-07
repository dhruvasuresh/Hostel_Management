<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Check if room_type is set
        if (!isset($_POST['room_type']) || empty($_POST['room_type'])) {
            throw new Exception("Please select a room type");
        }

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

        // Check if room_no is set
        if (!isset($_POST['room_no']) || empty($_POST['room_no'])) {
            throw new Exception("Please enter a room number");
        }

        $stmt = $pdo->prepare("
            INSERT INTO room (room_no, room_type, capacity, status) 
            VALUES (?, ?, ?, 'Available')
        ");
        
        $stmt->execute([
            $_POST['room_no'],
            $_POST['room_type'],
            $capacity
        ]);

        $_SESSION['success'] = "Room added successfully";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding room: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Add New Room</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Room Number</label>
                        <input type="text" name="room_no" class="form-control" required>
                        <small class="text-muted">Enter a unique room number</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Room Type</label>
                        <select name="room_type" class="form-control" required>
                            <option value="">Select Room Type</option>
                            <option value="Single">Single Room</option>
                            <option value="Double">Double Room</option>
                            <option value="Triple">Triple Room</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Room</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
