<?php
// Session timeout settings (30 minutes) - MUST BE BEFORE session_start()
ini_set('session.gc_maxlifetime', 1800);
session_set_cookie_params(1800);

// Now start the session
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'hostel_management';
$username = 'root';
$password = '';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kolkata');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Site configuration
define('SITE_NAME', 'Hostel Management System');
define('SITE_URL', 'http://localhost/hostel_management');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/hostel_management/uploads/');

// Check session timeout
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time >= 1800) { // 30 minutes
        session_destroy();
        header("Location: " . SITE_URL . "/index.php?msg=Session expired due to inactivity");
        exit();
    }
}
$_SESSION['last_activity'] = time();
?>
