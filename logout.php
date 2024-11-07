<?php
session_start();
session_destroy();

if (isset($_GET['timeout'])) {
    header("Location: index.php?msg=Your session has timed out due to inactivity. Please login again.");
} else {
    header("Location: index.php");
}
exit();
?>
