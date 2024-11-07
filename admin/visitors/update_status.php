<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: list.php");
    exit();
}

$visitor_id = $_GET['id'];
$status = $_GET['status'];

// Validate status
if (!in_array($status, ['Approved', 'Rejected'])) {
    $_SESSION['error'] = "Invalid status";
    header("Location: list.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE visitor 
        SET status = ?, 
            updated_at = NOW()
        WHERE visitor_id = ?
    ");
    
    $stmt->execute([$status, $visitor_id]);
    
    $_SESSION['success'] = "Visitor request has been " . strtolower($status);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error updating status: " . $e->getMessage();
}

header("Location: list.php");
exit();
?>
