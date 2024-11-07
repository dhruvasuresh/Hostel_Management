<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$room_no = $_GET['id'];

try {
    // Check if room has active allotments
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM allotment 
        WHERE room_no = ? AND status = 'Active'
    ");
    $stmt->execute([$room_no]);
    
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Cannot delete room: Room has active occupants";
        header("Location: list.php");
        exit();
    }

    // Delete room
    $stmt = $pdo->prepare("DELETE FROM room WHERE room_no = ?");
    $stmt->execute([$room_no]);

    $_SESSION['success'] = "Room deleted successfully";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting room: " . $e->getMessage();
}

header("Location: list.php");
exit();
?>
