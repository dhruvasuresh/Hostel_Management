<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$visitor_id = $_GET['id'];

try {
    // Delete visitor record
    $stmt = $pdo->prepare("DELETE FROM visitor WHERE visitor_id = ?");
    $stmt->execute([$visitor_id]);
    
    $_SESSION['success'] = "Visitor record deleted successfully";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting visitor record: " . $e->getMessage();
}

header("Location: list.php");
exit();
?>
