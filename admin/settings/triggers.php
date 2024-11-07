<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['toggle_trigger'])) {
            $trigger_name = $_POST['trigger_name'];
            $trigger_status = $_POST['trigger_status'];
            
            if ($trigger_status == 'enabled') {
                // Disable trigger
                $pdo->exec("DROP TRIGGER IF EXISTS $trigger_name");
            } else {
                // Enable trigger
                switch($trigger_name) {
                    case 'before_student_insert':
                        $pdo->exec("
                            CREATE TRIGGER before_student_insert 
                            BEFORE INSERT ON student
                            FOR EACH ROW 
                            SET NEW.age = TIMESTAMPDIFF(YEAR, NEW.date_of_birth, CURDATE())
                        ");
                        break;
                    case 'after_room_update':
                        $pdo->exec("
                            CREATE TRIGGER after_room_update 
                            AFTER UPDATE ON room
                            FOR EACH ROW 
                            BEGIN
                                IF NEW.occupancy >= NEW.capacity THEN
                                    UPDATE room SET status = 'Full' WHERE room_no = NEW.room_no;
                                ELSEIF NEW.occupancy < NEW.capacity THEN
                                    UPDATE room SET status = 'Available' WHERE room_no = NEW.room_no;
                                END IF;
                            END
                        ");
                        break;
                }
            }
            $_SESSION['success'] = "Trigger status updated successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: triggers.php");
    exit();
}

// Get current trigger status
$triggers = $pdo->query("SHOW TRIGGERS")->fetchAll();
$trigger_status = array();
foreach ($triggers as $trigger) {
    $trigger_status[$trigger['Trigger']] = 'enabled';
}

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Database Triggers Management</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Trigger Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>before_student_insert</td>
                            <td>Automatically calculates student age from date of birth</td>
                            <td>
                                <span class="badge bg-<?= isset($trigger_status['before_student_insert']) ? 'success' : 'danger' ?>">
                                    <?= isset($trigger_status['before_student_insert']) ? 'Enabled' : 'Disabled' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="trigger_name" value="before_student_insert">
                                    <input type="hidden" name="trigger_status" 
                                           value="<?= isset($trigger_status['before_student_insert']) ? 'enabled' : 'disabled' ?>">
                                    <button type="submit" name="toggle_trigger" class="btn btn-sm btn-primary">
                                        <?= isset($trigger_status['before_student_insert']) ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td>after_room_update</td>
                            <td>Automatically updates room status based on occupancy</td>
                            <td>
                                <span class="badge bg-<?= isset($trigger_status['after_room_update']) ? 'success' : 'danger' ?>">
                                    <?= isset($trigger_status['after_room_update']) ? 'Enabled' : 'Disabled' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="trigger_name" value="after_room_update">
                                    <input type="hidden" name="trigger_status" 
                                           value="<?= isset($trigger_status['after_room_update']) ? 'enabled' : 'disabled' ?>">
                                    <button type="submit" name="toggle_trigger" class="btn btn-sm btn-primary">
                                        <?= isset($trigger_status['after_room_update']) ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/admin_footer.php'; ?>
