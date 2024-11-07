<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$allotment_id = $_GET['id'];

try {
    $pdo->beginTransaction();

    // First check if the fee is paid
    $stmt = $pdo->prepare("
        SELECT a.*, r.room_no,
               (SELECT status 
                FROM fee 
                WHERE student_id = a.student_id 
                AND created_at >= a.created_at 
                ORDER BY created_at DESC 
                LIMIT 1) as fee_status
        FROM allotment a
        JOIN room r ON a.room_no = r.room_no
        WHERE a.allotment_id = ?
    ");
    $stmt->execute([$allotment_id]);
    $allotment = $stmt->fetch();

    if (!$allotment) {
        throw new Exception("Allotment not found");
    }

    // Prevent deletion if fee is paid
    if ($allotment['fee_status'] === 'Paid') {
        throw new Exception("Cannot delete allotment after fee payment");
    }

    // If allotment is active, update room occupancy
    if ($allotment['status'] == 'Active') {
        // Update room occupancy
        $stmt = $pdo->prepare("
            UPDATE room 
            SET occupancy = occupancy - 1,
                status = 'Available'
            WHERE room_no = ?
        ");
        $stmt->execute([$allotment['room_no']]);
    }

    // Cancel any pending fees
    $stmt = $pdo->prepare("
        UPDATE fee 
        SET status = 'Cancelled'
        WHERE student_id = ? 
        AND status = 'Pending'
        AND created_at >= ?
    ");
    $stmt->execute([$allotment['student_id'], $allotment['created_at']]);

    // Delete the allotment
    $stmt = $pdo->prepare("DELETE FROM allotment WHERE allotment_id = ?");
    $stmt->execute([$allotment_id]);

    $pdo->commit();
    $_SESSION['success'] = "Allotment deleted successfully";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: list.php");
exit();
?>
