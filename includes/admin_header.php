<?php require_once 'auth.php'; checkAdmin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/admin.css" rel="stylesheet">
    <script>
        // Auto logout JavaScript
        let timeout;
        function resetTimer() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                window.location.href = '<?= SITE_URL ?>/logout.php?timeout=1';
            }, 1800000); // 30 minutes
        }
        
        document.addEventListener('mousemove', resetTimer);
        document.addEventListener('keypress', resetTimer);
        resetTimer();
    </script>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="d-flex flex-column flex-shrink-0 p-3 text-white" style="width: 280px;">
                <a href="<?= SITE_URL ?>/admin/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                    <span class="fs-4"><?= SITE_NAME ?></span>
                </a>
                <hr>
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-link text-white">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?= SITE_URL ?>/admin/students/list.php" class="nav-link text-white">
                            <i class="fas fa-users me-2"></i>
                            Students
                        </a>
                    </li>
                    <li>
                        <a href="<?= SITE_URL ?>/admin/rooms/list.php" class="nav-link text-white">
                            <i class="fas fa-door-open me-2"></i>
                            Rooms
                        </a>
                    </li>
                    <li>
                        <a href="<?= SITE_URL ?>/admin/staff/list.php" class="nav-link text-white">
                            <i class="fas fa-user-tie me-2"></i>
                            Staff
                        </a>
                    </li>
                    <li>
                        <a href="<?= SITE_URL ?>/admin/complaints/list.php" class="nav-link text-white">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Complaints
                        </a>
                    </li>
                    <li>
                        <a href="<?= SITE_URL ?>/admin/fees/list.php" class="nav-link text-white">
                            <i class="fas fa-money-bill me-2"></i>
                            Fees
                        </a>
                    </li>
                    <li>
                        <a href="<?= SITE_URL ?>/admin/visitors/list.php" class="nav-link text-white">
                            <i class="fas fa-walking me-2"></i>
                            Visitors
                        </a>
                    </li>
                    <li>
                        <a href="<?= SITE_URL ?>/admin/allotment/list.php" class="nav-link text-white">
                            <i class="fas fa-bed me-2"></i>
                            Allotment
                        </a>
                    </li>

                    <!-- New Reports Section -->
                    <li>
                        <a href="#" class="nav-link text-white" data-bs-toggle="collapse" data-bs-target="#reportsSubmenu">
                            <i class="fas fa-chart-bar me-2"></i>
                            Reports & Analytics
                        </a>
                        <div class="collapse" id="reportsSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li>
                                    <a href="<?= SITE_URL ?>/admin/reports/advanced_search.php" class="nav-link text-white">
                                        <i class="fas fa-search me-2"></i>
                                        Advanced Search
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= SITE_URL ?>/admin/reports/student_history.php" class="nav-link text-white">
                                        <i class="fas fa-history me-2"></i>
                                        Student History
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= SITE_URL ?>/admin/reports/dashboard_stats.php" class="nav-link text-white">
                                        <i class="fas fa-chart-pie me-2"></i>
                                        Statistics
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- New Settings Section -->
                    <li>
                        <a href="#" class="nav-link text-white" data-bs-toggle="collapse" data-bs-target="#settingsSubmenu">
                            <i class="fas fa-cogs me-2"></i>
                            Settings
                        </a>
                        <div class="collapse" id="settingsSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li>
                                    <a href="<?= SITE_URL ?>/admin/settings/triggers.php" class="nav-link text-white">
                                        <i class="fas fa-database me-2"></i>
                                        Database Triggers
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
                <hr>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" 
                       id="dropdownUser1" data-bs-toggle="dropdown">
                        <img src="<?= SITE_URL ?>/assets/images/profile-default.png" alt="" width="32" height="32" 
                             class="rounded-circle me-2">
                        <strong><?= $_SESSION['name'] ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/admin/profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/logout.php">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex-grow-1 p-3">
