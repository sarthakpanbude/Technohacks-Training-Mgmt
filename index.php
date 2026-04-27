<?php
session_start();
require_once 'config/db.php';

// If user is already logged in, redirect to admin dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    header("Location: admin/dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please fill all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            header("Location: admin/dashboard.php");
            exit;
        } else {
            $error = "Invalid admin credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | TechnoHacks Solutions ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">

    <div class="auth-card animate-fade-in">
        <div class="auth-logo text-center">
            <img src="assets/img/logo.png" alt="TechnoHacks Solutions" style="max-height: 100px; width: auto; margin-bottom: 0.5rem;">
            <h4 class="fw-bold text-primary mb-1">TechnoHacks ERP</h4>
            <p class="text-muted small">Training Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small border-0 text-center mb-3">
                <i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Inquiry Action -->
        <div class="mb-4 p-4 rounded-4 border bg-light text-center shadow-sm border-primary border-opacity-25">
            <h6 class="fw-bold mb-2">Interested in a Course?</h6>
            <p class="small text-muted mb-3">Submit your inquiry and our team will get in touch with you shortly.</p>
            <a href="inquiry.php" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm fw-bold">
                <i class="fas fa-paper-plane me-2"></i>Apply for Admission
            </a>
        </div>

        <div class="text-center mb-4 position-relative">
            <hr>
            <span class="bg-white px-3 text-muted small position-absolute top-50 start-50 translate-middle">ADMIN LOGIN</span>
        </div>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Username</label>
                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-user-shield text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0 ps-0" placeholder="admin" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted text-uppercase">Password</label>
                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-dark w-100 py-3 rounded-4 shadow fw-bold">
                LOGIN TO PORTAL <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-2 border-top">
            <p class="small text-muted mb-2">© 2026 TechnoHacks Solutions Pvt. Ltd.</p>
            <a href="register.php" class="text-primary text-decoration-none small fw-bold">Create Admin Account</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
