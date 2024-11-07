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
    $pdo->beginTransaction();

    // Check if fee exists and is pending
    $stmt = $pdo->prepare("SELECT status FROM fee WHERE fee_id = ?");
    $stmt->execute([$fee_id]);
    $fee = $stmt->fetch();

    if (!$fee) {
        throw new Exception("Fee record not found");
    }

    if ($fee['status'] !== 'Pending') {
        throw new Exception("This fee is already " . strtolower($fee['status']));
    }

    // Update fee status to paid
    $stmt = $pdo->prepare("
        UPDATE fee 
        SET status = 'Paid', 
            paid_on = CURRENT_DATE 
        WHERE fee_id = ?
    ");
    $stmt->execute([$fee_id]);

    $pdo->commit();
    $_SESSION['success'] = "Fee marked as paid successfully";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: list.php");
exit();
?>
