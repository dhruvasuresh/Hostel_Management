<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkStudent();

// Fetch student details with room information
$stmt = $pdo->prepare("
    SELECT s.*, r.room_no, r.room_type
    FROM student s
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN room r ON a.room_no = r.room_no
    WHERE s.student_id = ?
");
$stmt->execute([$_SESSION['actual_id']]);
$student = $stmt->fetch();

// Fetch pending fees
$stmt = $pdo->prepare("
    SELECT SUM(amount) as total_pending
    FROM fee 
    WHERE student_id = ? AND status = 'Pending'
");
$stmt->execute([$_SESSION['actual_id']]);
$pending_fees = $stmt->fetch()['total_pending'] ?? 0;

// Fetch recent complaints
$stmt = $pdo->prepare("
    SELECT * FROM complaint 
    WHERE student_id = ? 
    ORDER BY logged_on DESC LIMIT 5
");
$stmt->execute([$_SESSION['actual_id']]);
$complaints = $stmt->fetchAll();

// Fetch recent visitors
$stmt = $pdo->prepare("
    SELECT * FROM visitor 
    WHERE student_id = ? 
    ORDER BY visit_date DESC LIMIT 5
");
$stmt->execute([$_SESSION['actual_id']]);
$visitors = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Hostel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: #343a40;
            padding-top: 20px;
            color: white;
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: 12px 20px;
            margin: 5px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.1);
            border-left: 4px solid #fff;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 8px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem;
        }

        .user-info {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,.1);
            margin-bottom: 20px;
        }

        .student-name {
            color: rgba(255,255,255,.9);
            font-size: 16px;
            margin-top: 10px;
            padding: 5px 20px;
        }

        .welcome-header {
            color: #4e73df;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            transition: transform 0.2s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .badge {
            padding: 0.5em 0.8em;
        }

        .list-group-item {
            border-left: none;
            border-right: none;
            border-radius: 0;
        }

        .list-group-item:first-child {
            border-top: none;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-info">
            <h5>Hostel Management System</h5>
            <p class="student-name">
                <i class="fas fa-user"></i> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
            </p>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="room/view.php">
                    <i class="fas fa-bed"></i> Room
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="fees/list.php">
                    <i class="fas fa-money-bill"></i> Fees
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="complaints/list.php">
                    <i class="fas fa-exclamation-circle"></i> Complaints
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="visitors/list.php">
                    <i class="fas fa-users"></i> Visitors
                </a>
            </li>
            <li class="nav-item mt-5">
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="welcome-header">Welcome, <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>!</h2>

        <!-- Summary Cards -->
        <div class="row">
            <!-- Room Info -->
            <div class="col-xl-6 col-md-6">
                <div class="card summary-card bg-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Room Details</div>
                                <div class="h5 mb-0">
                                    <?php if ($student['room_no']): ?>
                                        Room <?= $student['room_no'] ?> (<?= $student['room_type'] ?> Class)
                                    <?php else: ?>
                                        Not Assigned
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-bed fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Fees -->
            <div class="col-xl-6 col-md-6">
                <div class="card summary-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Pending Fees</div>
                                <div class="h5 mb-0">₹<?= number_format($pending_fees, 2) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row mt-4">
            <!-- Recent Complaints -->
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Recent Complaints</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($complaints): ?>
                            <div class="list-group">
                                <?php foreach ($complaints as $complaint): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($complaint['type']) ?></h6>
                                            <small><?= date('d M Y', strtotime($complaint['logged_on'])) ?></small>
                                        </div>
                                        <p class="mb-1"><?= htmlspecialchars($complaint['description']) ?></p>
                                        <small>Status: 
                                            <span class="badge bg-<?= 
                                                $complaint['status'] == 'Resolved' ? 'success' : 
                                                ($complaint['status'] == 'In Progress' ? 'warning' : 'danger') 
                                            ?>">
                                                <?= $complaint['status'] ?>
                                            </span>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No complaints found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Visitors -->
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Recent Visitors</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($visitors): ?>
                            <div class="list-group">
                                <?php foreach ($visitors as $visitor): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?>
                                            </h6>
                                            <small><?= date('d M Y', strtotime($visitor['visit_date'])) ?></small>
                                        </div>
                                        <p class="mb-1">Relation: <?= htmlspecialchars($visitor['relation']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No recent visitors</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
