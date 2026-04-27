<?php
session_start();
require_once 'config/db.php';

// If user is already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else if ($_SESSION['role'] == 'teacher') header("Location: teacher/dashboard.php");
    else if ($_SESSION['role'] == 'student') header("Location: student/dashboard.php");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            if ($user['role'] == 'admin') header("Location: admin/dashboard.php");
            else if ($user['role'] == 'teacher') header("Location: teacher/dashboard.php");
            else if ($user['role'] == 'student') header("Location: student/dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TechnoHacks Solutions ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">

    <div class="auth-card animate-fade-in">
        <div class="auth-logo">
            <i class="fas fa-microchip me-2"></i>TechnoHacks Solutions
        </div>
        <h4 class="text-center mb-4" style="font-weight: 700;">ERP Portal Login</h4>
        
        <!-- Role Tabs -->
        <ul class="nav nav-pills nav-justified mb-4 login-tabs" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" onclick="updateCredentials('admin')" data-bs-toggle="pill" type="button">Admin</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" onclick="updateCredentials('teacher')" data-bs-toggle="pill" type="button">Teacher</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" onclick="updateCredentials('student')" data-bs-toggle="pill" type="button">Student</button>
            </li>
        </ul>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3" style="border-radius: 10px; font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" id="login-username" class="form-control border-start-0 ps-0" placeholder="Enter username" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="login-password" class="form-control border-start-0 ps-0" placeholder="Enter password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3 py-2 fw-bold">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
            <div class="text-center">
                <a href="#" class="text-decoration-none small text-muted">Forgot password?</a>
            </div>
        </form>

        <div class="mt-4 p-3 bg-light rounded-3 border">
            <p class="small text-muted mb-2 fw-bold"><i class="fas fa-info-circle me-1"></i> Demo Access:</p>
            <div class="d-flex justify-content-between align-items-center">
                <code class="small" id="demo-creds">admin / admin123</code>
                <button class="btn btn-sm btn-outline-secondary border-0" onclick="copyDemo()"><i class="far fa-copy"></i></button>
            </div>
        </div>

        <hr class="my-4">
        <div class="text-center small">
            <span class="text-muted">Don't have an account?</span> 
            <a href="register.php" class="text-primary fw-bold text-decoration-none">Register Here</a>
        </div>
        <div class="text-center small mt-2">
            <span class="text-muted">Not a student yet?</span> 
            <a href="student/apply.php" class="text-primary fw-bold text-decoration-none">Apply Now</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCredentials(role) {
            const userField = document.getElementById('login-username');
            const passField = document.getElementById('login-password');
            const demoText = document.getElementById('demo-creds');
            
            let u = '', p = '';
            if(role === 'admin') {
                u = 'admin'; p = 'admin123';
            } else if(role === 'teacher') {
                u = 'teacher'; p = 'teacher123';
            } else if(role === 'student') {
                u = 'student'; p = 'student123';
            }
            
            demoText.innerText = u + ' / ' + p;
            userField.value = u;
            passField.value = p;
        }

        function copyDemo() {
            const creds = document.getElementById('demo-creds').innerText.split(' / ');
            document.getElementById('login-username').value = creds[0];
            document.getElementById('login-password').value = creds[1];
        }
        
        // Initialize with admin creds
        window.onload = () => updateCredentials('admin');
    </script>
</body>
</html>
