<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$student_id = $_GET['id'];

try {
    $pdo->beginTransaction();

    // Delete user account first (due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
    $stmt->execute([$student_id]);

    // Delete student (this will cascade delete related records due to foreign key constraints)
    $stmt = $pdo->prepare("DELETE FROM student WHERE student_id = ?");
    $stmt->execute([$student_id]);

    $pdo->commit();
    $_SESSION['success'] = "Student deleted successfully";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
}

header("Location: list.php");
exit();
?>
