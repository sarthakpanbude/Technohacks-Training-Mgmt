<?php
session_start();
require_once 'config/db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $error = "Please fill all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already taken.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, 'admin', ?, ?)");
                $stmt->execute([$username, $hashed_password, $email, $full_name]);
                $success = "Admin account created successfully! You can now login.";
            } catch (PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration | TechnoHacks Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-card { max-width: 500px; }
    </style>
</head>
<body class="auth-wrapper">

    <div class="auth-card animate-fade-in">
        <div class="auth-logo text-center">
            <img src="assets/img/logo.png" alt="Logo" style="max-height: 80px; margin-bottom: 1rem;">
            <h4 class="fw-bold text-primary">Admin Registration</h4>
            <p class="text-muted small">Create a new administrative account.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small border-0 text-center mb-3">
                <i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-2 small border-0 text-center mb-3">
                <i class="fas fa-check-circle me-1"></i> <?php echo $success; ?>
                <div class="mt-2"><a href="index.php" class="btn btn-sm btn-success">Login Now</a></div>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-id-badge text-muted"></i></span>
                    <input type="text" name="full_name" class="form-control border-start-0 ps-0" placeholder="e.g. Sarthak Panbude" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="admin@technohacks.com" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-user-shield text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0 ps-0" placeholder="Choose username" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Confirm</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 shadow fw-bold">
                REGISTER ADMIN <i class="fas fa-user-plus ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <a href="index.php" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>

</body>
</html>
