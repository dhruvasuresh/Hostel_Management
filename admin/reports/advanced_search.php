<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

$search_results = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['search_type'])) {
            switch($_POST['search_type']) {
                case 'pending_fees_complaints':
                    // Nested query to find students with pending fees AND active complaints
                    $query = "
                        SELECT DISTINCT s.* 
                        FROM student s 
                        WHERE s.student_id IN (
                            SELECT f.student_id 
                            FROM fee f 
                            WHERE f.status = 'Pending'
                            AND EXISTS (
                                SELECT 1 
                                FROM complaint c 
                                WHERE c.student_id = f.student_id 
                                AND c.status != 'Resolved'
                            )
                        )
                    ";
                    break;

                case 'full_rooms':
                    // Nested query to find rooms with maximum occupancy
                    $query = "
                        SELECT r.*, 
                        (SELECT COUNT(*) 
                         FROM allotment a 
                         WHERE a.room_no = r.room_no 
                         AND a.status = 'Active') as current_occupants
                        FROM room r
                        WHERE r.occupancy = r.capacity
                    ";
                    break;
            }
            
            $search_results = $pdo->query($query)->fetchAll();
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Advanced Search</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <select name="search_type" class="form-control" required>
                            <option value="">Select Search Type</option>
                            <option value="pending_fees_complaints">Students with Pending Fees & Active Complaints</option>
                            <option value="full_rooms">Fully Occupied Rooms</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($search_results)): ?>
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <?php foreach(array_keys($search_results[0]) as $column): ?>
                            <th><?= ucwords(str_replace('_', ' ', $column)) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($search_results as $result): ?>
                        <tr>
                            <?php foreach($result as $value): ?>
                            <td><?= htmlspecialchars($value) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/admin_footer.php'; ?>
