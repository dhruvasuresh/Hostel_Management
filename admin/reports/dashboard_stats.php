<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Aggregate queries
$queries = [
    'room_stats' => "
        SELECT 
            room_type,
            COUNT(*) as total_rooms,
            SUM(occupancy) as total_occupants,
            AVG(occupancy/capacity * 100) as occupancy_rate,
            SUM(CASE WHEN status = 'Full' THEN 1 ELSE 0 END) as full_rooms,
            SUM(fees * occupancy) as total_fees
        FROM room
        GROUP BY room_type
    ",
    
    'complaint_stats' => "
        SELECT 
            type as complaint_type,
            COUNT(*) as total_complaints,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
            AVG(DATEDIFF(IFNULL(resolved_on, CURRENT_DATE), logged_on)) as avg_resolution_days
        FROM complaint
        GROUP BY type
    ",
    
    'fee_collection' => "
        SELECT 
            YEAR(created_at) as year,
            MONTH(created_at) as month,
            SUM(amount) as total_amount,
            COUNT(*) as total_transactions,
            SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as collected_amount,
            SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_amount
        FROM fee
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY year DESC, month DESC
        LIMIT 12
    "
];

$stats = [];
foreach ($queries as $key => $query) {
    $stats[$key] = $pdo->query($query)->fetchAll();
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Dashboard Statistics</h2>

    <!-- Room Statistics -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="m-0">Room Statistics</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Room Type</th>
                            <th>Total Rooms</th>
                            <th>Occupancy Rate</th>
                            <th>Full Rooms</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats['room_stats'] as $stat): ?>
                        <tr>
                            <td><?= $stat['room_type'] ?></td>
                            <td><?= $stat['total_rooms'] ?></td>
                            <td><?= number_format($stat['occupancy_rate'], 1) ?>%</td>
                            <td><?= $stat['full_rooms'] ?></td>
                            <td>₹<?= number_format($stat['total_fees'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Complaint Statistics -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="m-0">Complaint Statistics</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Pending</th>
                            <th>In Progress</th>
                            <th>Resolved</th>
                            <th>Avg. Resolution Time (Days)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats['complaint_stats'] as $stat): ?>
                        <tr>
                            <td><?= $stat['complaint_type'] ?></td>
                            <td><?= $stat['total_complaints'] ?></td>
                            <td><?= $stat['pending'] ?></td>
                            <td><?= $stat['in_progress'] ?></td>
                            <td><?= $stat['resolved'] ?></td>
                            <td><?= number_format($stat['avg_resolution_days'], 1) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Fee Collection Statistics -->
    <div class="card shadow">
        <div class="card-header">
            <h5 class="m-0">Monthly Fee Collection</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Total Amount</th>
                            <th>Collected</th>
                            <th>Pending</th>
                            <th>Collection Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats['fee_collection'] as $stat): ?>
                        <tr>
                            <td><?= date('F Y', mktime(0, 0, 0, $stat['month'], 1, $stat['year'])) ?></td>
                            <td>₹<?= number_format($stat['total_amount'], 2) ?></td>
                            <td>₹<?= number_format($stat['collected_amount'], 2) ?></td>
                            <td>₹<?= number_format($stat['pending_amount'], 2) ?></td>
                            <td>
                                <?= number_format(($stat['collected_amount'] / $stat['total_amount']) * 100, 1) ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
