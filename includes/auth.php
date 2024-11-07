<?php
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . SITE_URL . "/login.php");
        exit();
    }
}

function checkAdmin() {
    checkLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: " . SITE_URL . "/unauthorized.php");
        exit();
    }
}

function checkStudent() {
    checkLogin();
    if ($_SESSION['role'] !== 'student') {
        header("Location: " . SITE_URL . "/unauthorized.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function getCurrentUserId() {
    return $_SESSION['actual_id'] ?? null;
}
?>
