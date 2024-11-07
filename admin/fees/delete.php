<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$fee_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM fee WHERE fee_id = ?");
    $stmt->execute([$fee_id]);
    
    $_SESSION['success'] = "Fee record deleted successfully";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting fee record: " . $e->getMessage();
}

header("Location: list.php");
exit();
?>
