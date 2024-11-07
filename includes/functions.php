<?php
// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('d-m-Y', strtotime($date));
}

// Get room type display
function getRoomTypeDisplay($type) {
    switch($type) {
        case 'S': return 'S Class (Single Room - ₹65,000)';
        case 'A': return 'A Class (Double Room - ₹55,000)';
        case 'B': return 'B Class (Triple Room - ₹45,000)';
        default: return 'Unknown';
    }
}

// Get room fees
function getRoomFees($type) {
    switch($type) {
        case 'S': return 65000;
        case 'A': return 55000;
        case 'B': return 45000;
        default: return 0;
    }
}

// Clean input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
        ceil($length/strlen($x)))), 1, $length);
}

// Upload file
function uploadFile($file, $destination) {
    $fileName = basename($file['name']);
    $targetPath = UPLOAD_PATH . $destination . '/' . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    return false;
}

// Get complaint status badge
function getStatusBadge($status) {
    switch($status) {
        case 'Pending':
            return '<span class="badge bg-warning">Pending</span>';
        case 'In Progress':
            return '<span class="badge bg-info">In Progress</span>';
        case 'Resolved':
            return '<span class="badge bg-success">Resolved</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

// Check if room is available
function isRoomAvailable($roomNo) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT capacity, occupancy FROM room WHERE room_no = ?");
    $stmt->execute([$roomNo]);
    $room = $stmt->fetch();
    
    return $room && $room['occupancy'] < $room['capacity'];
}

// Get student details
function getStudentDetails($studentId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM student WHERE student_id = ?");
    $stmt->execute([$studentId]);
    return $stmt->fetch();
}

// Get pending fees
function getPendingFees($studentId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total 
        FROM fee 
        WHERE student_id = ? AND status = 'Pending'
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetchColumn() ?: 0;
}

// Send notification
function sendNotification($userId, $message, $type = 'info') {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message, type) 
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$userId, $message, $type]);
}

// Log activity
function logActivity($userId, $action, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, details) 
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$userId, $action, $details]);
}
?>
