<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$complaint_id = $_GET['id'];

try {
    // Delete complaint
    $stmt = $pdo->prepare("DELETE FROM complaint WHERE complaint_id = ?");
    $stmt->execute([$complaint_id]);
    
    $_SESSION['success'] = "Complaint deleted successfully";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting complaint: " . $e->getMessage();
}

header("Location: list.php");
exit();
?>
