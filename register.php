<?php
session_start();
require_once 'config/db.php';

// If user is already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else if ($_SESSION['role'] == 'teacher') header("Location: teacher/dashboard.php");
    else if ($_SESSION['role'] == 'student') header("Location: student/dashboard.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'admin';

    // Validate role
    $allowed_roles = ['admin', 'teacher', 'student'];
    if (!in_array($role, $allowed_roles)) {
        $error = "Invalid role selected.";
    } elseif (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill all fields.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already taken.";
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered.";
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $hashed_password, $role, $email, $full_name]);
                    $user_id = $pdo->lastInsertId();

                    // Create role-specific record
                    if ($role === 'teacher') {
                        $stmt = $pdo->prepare("INSERT INTO teachers (user_id) VALUES (?)");
                        $stmt->execute([$user_id]);
                    } elseif ($role === 'student') {
                        $stmt = $pdo->prepare("INSERT INTO students (user_id) VALUES (?)");
                        $stmt->execute([$user_id]);
                    }

                    $pdo->commit();

                    // Auto-login
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['role'] = $role;
                    $_SESSION['full_name'] = $full_name;

                    if ($role == 'admin') header("Location: admin/dashboard.php");
                    else if ($role == 'teacher') header("Location: teacher/dashboard.php");
                    else if ($role == 'student') header("Location: student/dashboard.php");
                    exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Registration failed. Please try again.";
                }
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
    <title>Register | TechnoHacks Solutions ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-card {
            max-width: 540px;
        }
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        .role-option {
            flex: 1;
            text-align: center;
            padding: 1rem 0.5rem;
            border: 2px solid var(--border);
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--bg-main);
        }
        .role-option:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .role-option.selected {
            border-color: var(--primary);
            background: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .role-option i {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 0.4rem;
            color: var(--secondary);
            transition: color 0.3s ease;
        }
        .role-option.selected i {
            color: var(--primary);
        }
        .role-option span {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.3s ease;
        }
        .role-option.selected span {
            color: var(--primary);
        }
        .password-strength {
            height: 4px;
            border-radius: 4px;
            margin-top: 6px;
            transition: all 0.3s ease;
            background: var(--border);
        }
        .password-strength .bar {
            height: 100%;
            border-radius: 4px;
            transition: all 0.4s ease;
            width: 0%;
        }
        .strength-weak { background: var(--danger); width: 25% !important; }
        .strength-fair { background: var(--warning); width: 50% !important; }
        .strength-good { background: var(--info); width: 75% !important; }
        .strength-strong { background: var(--success); width: 100% !important; }
    </style>
</head>
<body class="auth-wrapper">

    <div class="auth-card animate-fade-in">
        <div class="auth-logo">
            <i class="fas fa-microchip me-2"></i>TechnoHacks Solutions
        </div>
        <h4 class="text-center mb-4" style="font-weight: 700;">Create Account</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3" style="border-radius: 10px; font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success mb-3" style="border-radius: 10px; font-size: 0.9rem;">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="registerForm">
            <!-- Role Selector -->
            <label class="form-label small fw-bold mb-2">Select Role</label>
            <div class="role-selector">
                <div class="role-option selected" onclick="selectRole('admin', this)">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin</span>
                </div>
                <div class="role-option" onclick="selectRole('teacher', this)">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teacher</span>
                </div>
                <div class="role-option" onclick="selectRole('student', this)">
                    <i class="fas fa-user-graduate"></i>
                    <span>Student</span>
                </div>
            </div>
            <input type="hidden" name="role" id="role-input" value="admin">

            <div class="mb-3">
                <label class="form-label small fw-bold">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="full_name" id="full_name" class="form-control border-start-0 ps-0" placeholder="Enter full name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" id="email" class="form-control border-start-0 ps-0" placeholder="Enter email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-at text-muted"></i></span>
                    <input type="text" name="username" id="username" class="form-control border-start-0 ps-0" placeholder="Choose a username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required minlength="3">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="password" class="form-control border-start-0 ps-0" placeholder="Create a password" required minlength="6" oninput="checkStrength(this.value)">
                    <span class="input-group-text bg-light border-start-0" style="cursor:pointer;" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye text-muted"></i>
                    </span>
                </div>
                <div class="password-strength">
                    <div class="bar" id="strength-bar"></div>
                </div>
                <small class="text-muted" id="strength-text" style="font-size: 0.75rem;"></small>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0 ps-0" placeholder="Confirm your password" required minlength="6">
                    <span class="input-group-text bg-light border-start-0" style="cursor:pointer;" onclick="togglePassword('confirm_password', this)">
                        <i class="fas fa-eye text-muted"></i>
                    </span>
                </div>
                <small class="text-danger d-none" id="match-error" style="font-size: 0.75rem;">
                    <i class="fas fa-times-circle me-1"></i>Passwords do not match
                </small>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3 py-2 fw-bold" id="submitBtn">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>

        <hr class="my-4">
        <div class="text-center small">
            <span class="text-muted">Already have an account?</span> 
            <a href="index.php" class="text-primary fw-bold text-decoration-none">Sign In</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectRole(role, el) {
            document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('role-input').value = role;
        }

        function togglePassword(fieldId, el) {
            const field = document.getElementById(fieldId);
            const icon = el.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function checkStrength(password) {
            const bar = document.getElementById('strength-bar');
            const text = document.getElementById('strength-text');
            let strength = 0;

            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            bar.className = 'bar';
            if (password.length === 0) {
                text.textContent = '';
                return;
            }

            if (strength <= 1) {
                bar.classList.add('strength-weak');
                text.textContent = 'Weak password';
                text.style.color = '#ef4444';
            } else if (strength <= 2) {
                bar.classList.add('strength-fair');
                text.textContent = 'Fair password';
                text.style.color = '#f59e0b';
            } else if (strength <= 3) {
                bar.classList.add('strength-good');
                text.textContent = 'Good password';
                text.style.color = '#3b82f6';
            } else {
                bar.classList.add('strength-strong');
                text.textContent = 'Strong password';
                text.style.color = '#10b981';
            }
        }

        // Check password match
        document.getElementById('confirm_password').addEventListener('input', function () {
            const matchError = document.getElementById('match-error');
            if (this.value && this.value !== document.getElementById('password').value) {
                matchError.classList.remove('d-none');
            } else {
                matchError.classList.add('d-none');
            }
        });

        // Form validation before submit
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            if (pass !== confirm) {
                e.preventDefault();
                document.getElementById('match-error').classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
