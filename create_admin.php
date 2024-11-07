<?php
require_once 'includes/config.php';

try {
    $pdo->beginTransaction();
    
    // First, check if admin already exists and delete if it does
    $stmt = $pdo->prepare("DELETE FROM users WHERE role = 'admin'");
    $stmt->execute();
    
    $stmt = $pdo->prepare("DELETE FROM admin");
    $stmt->execute();

    // Create new admin record
    $stmt = $pdo->prepare("INSERT INTO admin (first_name, last_name, email, phone_no) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin', 'User', 'admin@hostel.com', '1234567890']);
    
    $adminId = $pdo->lastInsertId();
    
    // Create user account
    $username = "admin";
    $password = "admin123";
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, user_id) 
                          VALUES (?, ?, 'admin', ?)");
    $stmt->execute([$username, $hashedPassword, $adminId]);

    $pdo->commit();
    echo "<div style='margin: 20px; padding: 20px; border: 1px solid green; color: green;'>";
    echo "<h3>Admin user created successfully!</h3>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "</div>";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div style='margin: 20px; padding: 20px; border: 1px solid red; color: red;'>";
    echo "<h3>Error creating admin user:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Display current admin users for verification
try {
    echo "<div style='margin: 20px; padding: 20px; border: 1px solid blue;'>";
    echo "<h3>Current Admin Users:</h3>";
    $stmt = $pdo->query("
        SELECT u.username, u.role, a.first_name, a.last_name, a.email 
        FROM users u 
        JOIN admin a ON u.user_id = a.admin_id 
        WHERE u.role = 'admin'
    ");
    $admins = $stmt->fetchAll();
    
    if (count($admins) > 0) {
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>Username: " . $admin['username'] . 
                 " | Name: " . $admin['first_name'] . " " . $admin['last_name'] . 
                 " | Email: " . $admin['email'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No admin users found.</p>";
    }
    echo "</div>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error displaying admin users: " . $e->getMessage() . "</p>";
}
?>
