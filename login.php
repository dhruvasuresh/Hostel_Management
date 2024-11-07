<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = strtolower($_POST['username']); // Convert to lowercase to ensure matching
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    try {
        if ($role == 'admin') {
            // Admin login
            $stmt = $pdo->prepare("
                SELECT u.*, a.first_name, a.last_name
                FROM users u
                JOIN admin a ON u.user_id = a.admin_id
                WHERE u.username = ? AND u.role = 'admin'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['actual_id'] = $user['user_id'];
                header("Location: admin/dashboard.php");
                exit();
            }
        } else {
            // Student login
            $stmt = $pdo->prepare("
                SELECT u.*, s.first_name, s.last_name, s.date_of_birth
                FROM users u
                JOIN student s ON u.user_id = s.student_id
                WHERE u.username = ? AND u.role = 'student'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // For students, verify if password matches their DOB format
                $correctPassword = date('dmY', strtotime($user['date_of_birth']));
                if ($password === $correctPassword) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = 'student';
                    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['actual_id'] = $user['user_id'];
                    header("Location: student/dashboard.php");
                    exit();
                }
            }
        }
        $error = "Invalid username or password";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hostel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .role-selector {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .role-btn {
            padding: 10px 30px;
            margin: 0 10px;
            border: 2px solid #007bff;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-btn.active {
            background-color: #007bff;
            color: white;
        }
        .login-form {
            display: none;
        }
        .login-form.active {
            display: block;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Login</h2>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <!-- Role Selection -->
                        <div class="role-selector mb-4">
                            <div class="role-btn active" data-role="student">Student</div>
                            <div class="role-btn" data-role="admin">Admin</div>
                        </div>

                        <!-- Student Login Form -->
                        <div id="studentLogin" class="login-form active">
                            <div class="alert alert-info">
                                <strong>For Students:</strong>
                                <ul class="mb-0">
                                    <li>Username: Your first name (lowercase)</li>
                                    <li>Password: Your date of birth (DDMMYYYY)</li>
                                    <li>Example: If DOB is 5th March 2000, password would be 05032000</li>
                                </ul>
                            </div>

                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="role" value="student">
                                <div class="mb-3">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                    <small class="text-muted">Enter your first name in lowercase</small>
                                </div>

                                <div class="mb-3">
                                    <label>Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" class="form-control" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Enter your date of birth (DDMMYYYY)</small>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Login</button>
                                </div>
                            </form>
                        </div>

                        <!-- Admin Login Form -->
                        <div id="adminLogin" class="login-form">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="role" value="admin">
                                <div class="mb-3">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label>Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" class="form-control" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Login</button>
                                </div>
                            </form>
                        </div>

                        <div class="text-center mt-3">
                            <a href="index.php">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Role selector functionality
        const roleBtns = document.querySelectorAll('.role-btn');
        const loginForms = document.querySelectorAll('.login-form');

        roleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Update buttons
                roleBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update forms
                const role = this.dataset.role;
                loginForms.forEach(form => form.classList.remove('active'));
                document.getElementById(role + 'Login').classList.add('active');
            });
        });

        // Password toggle functionality
        const toggleBtns = document.querySelectorAll('.toggle-password');
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
    });
    </script>
</body>
</html>
