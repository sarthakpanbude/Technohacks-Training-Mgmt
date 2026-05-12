<?php
session_start();
require_once 'config/db.php';

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
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['role']      = $user['role'];
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            position: relative;
            overflow: hidden;
        }
        /* Decorative blobs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            z-index: 0;
        }
        body::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,0.25), transparent);
            top: -120px; right: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(16,185,129,0.2), transparent);
            bottom: -100px; left: -80px;
        }
        .login-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px;
            padding: 0 1rem;
        }
        .login-card {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px -10px rgba(0,0,0,0.12), 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid rgba(226,232,240,0.8);
            animation: loginFadeIn 0.5s cubic-bezier(0,0,0.2,1) forwards;
        }
        @keyframes loginFadeIn {
            from { opacity:0; transform:translateY(20px) scale(0.98); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }
        .login-logo { text-align:center; margin-bottom:1.75rem; }
        .login-logo img { max-height:70px; width:auto; }
        .login-logo h5 { font-size:1.15rem; font-weight:700; color:#0f172a; margin:0.5rem 0 0.25rem; }
        .login-logo p  { font-size:0.8rem; color:#64748b; margin:0; }

        .inquiry-box {
            background: linear-gradient(135deg, rgba(99,102,241,0.06), rgba(79,70,229,0.03));
            border: 1px solid rgba(99,102,241,0.18);
            border-radius: 14px;
            padding: 1.25rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .inquiry-box h6 { font-size:0.9rem; font-weight:700; color:#0f172a; margin-bottom:0.35rem; }
        .inquiry-box p  { font-size:0.78rem; color:#64748b; margin-bottom:0.85rem; }

        .divider-label {
            display: flex; align-items: center; gap: 12px;
            margin: 1.25rem 0;
            font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em;
        }
        .divider-label::before, .divider-label::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }

        .field-label {
            font-size: 0.72rem; font-weight: 700;
            color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em;
            margin-bottom: 6px; display: block;
        }
        .input-wrap {
            display: flex; align-items: center;
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            background: #fafafa;
            transition: all 0.2s;
            overflow: hidden;
        }
        .input-wrap:focus-within {
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
        }
        .input-wrap .input-icon {
            padding: 0 14px;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .input-wrap input {
            flex: 1; border: none; background: transparent; outline: none;
            padding: 0.75rem 0.75rem 0.75rem 0;
            font-size: 0.9rem; font-family: 'Outfit', sans-serif;
            color: #0f172a;
        }
        .input-wrap input::placeholder { color: #cbd5e1; }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            font-size: 0.9rem;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            letter-spacing: 0.03em;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(99,102,241,0.3);
            transition: all 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99,102,241,0.4);
        }
        .btn-login:active { transform: translateY(0); }

        .error-box {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.82rem;
            color: #dc2626;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <img src="assets/img/logo.png" alt="TechnoHacks Solutions">
            <h5>TechnoHacks ERP</h5>
            <p>Training Management System</p>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Course Inquiry CTA -->
        <div class="inquiry-box">
            <h6><i class="fas fa-graduation-cap me-2" style="color:#6366f1;"></i>Interested in a Course?</h6>
            <p>Submit your inquiry and our team will get in touch shortly.</p>
            <a href="inquiry.php" class="btn btn-primary rounded-pill px-4 w-100 text-decoration-none shadow-sm" style="font-size:0.85rem; color: white !important;">
                <i class="fas fa-paper-plane me-2"></i>Course Inquiry Form
            </a>
        </div>

        <!-- Divider -->
        <div class="divider-label">Admin Login</div>

        <!-- Login Form -->
        <form action="" method="POST">
            <div class="mb-3">
                <label class="field-label">Username</label>
                <div class="input-wrap">
                    <span class="input-icon"><i class="fas fa-user-shield"></i></span>
                    <input type="text" name="username" placeholder="admin" required autocomplete="username">
                </div>
            </div>
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="field-label mb-0">Password</label>
                    <a href="#" class="text-decoration-none" style="font-size:0.75rem;color:#6366f1;font-weight:600;">Forgot?</a>
                </div>
                <div class="input-wrap">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>
            <button type="submit" class="btn-login">
                Sign In to Portal &nbsp;<i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <!-- Footer -->
        <div class="text-center mt-5 pt-4" style="border-top:1px solid #f1f5f9;">
            <p style="font-size:0.75rem;color:#94a3b8;margin:0 0 8px;">© 2026 TechnoHacks Solutions Pvt. Ltd.</p>
            <a href="register.php" class="text-decoration-none" style="font-size:0.78rem;color:#6366f1;font-weight:600;">
                Create Admin Account <i class="fas fa-arrow-right ms-1" style="font-size:0.65rem;"></i>
            </a>
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
