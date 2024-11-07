<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Fetch all rooms
$stmt = $pdo->query("SELECT * FROM room ORDER BY room_no");
$rooms = $stmt->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Room Management</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Room
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Room No</th>
                            <th>Room Type</th>
                            <th>Capacity</th>
                            <th>Occupancy</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rooms as $room): ?>
                        <tr>
                            <td><?= htmlspecialchars($room['room_no']) ?></td>
                            <td><?= htmlspecialchars($room['room_type']) ?></td>
                            <td><?= $room['capacity'] ?></td>
                            <td><?= $room['occupancy'] ?>/<?= $room['capacity'] ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $room['status'] == 'Available' ? 'success' : 
                                    ($room['status'] == 'Full' ? 'warning' : 'danger') 
                                ?>">
                                    <?= htmlspecialchars($room['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $room['room_no'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $room['room_no'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="confirmDelete('<?= $room['room_no'] ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No rooms found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(roomNo) {
    if (confirm('Are you sure you want to delete this room?')) {
        window.location.href = `delete.php?id=${roomNo}`;
    }
}
</script>

<?php include '../../includes/admin_footer.php'; ?>
