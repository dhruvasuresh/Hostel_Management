<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$staff_id = $_GET['id'];

try {
    $pdo->beginTransaction();

    // Check for active complaints assigned to this staff member
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM complaint 
        WHERE staff_id = ? AND status != 'Resolved'
    ");
    $stmt->execute([$staff_id]);
    
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Cannot delete staff member: Has active complaints assigned";
        header("Location: list.php");
        exit();
    }

    // Delete user account if exists
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'staff'");
    $stmt->execute([$staff_id]);

    // Delete staff member
    $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);

    $pdo->commit();
    $_SESSION['success'] = "Staff member deleted successfully";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error deleting staff member: " . $e->getMessage();
}

header("Location: list.php");
exit();
?>
