<?php
session_start();
require_once '../config/db.php';

$pageTitle = "Student Application";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $address = trim($_POST['address']);
    $password = $_POST['password'];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill all required fields.";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "Email already registered.";
        } else {
            try {
                $pdo->beginTransaction();
                $username = strtolower(str_replace(' ', '', $full_name)) . rand(100, 999);
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, 'student', ?, ?)");
                $stmt->execute([$username, $hashed, $email, $full_name]);
                $user_id = $pdo->lastInsertId();

                $referral_code = strtoupper(substr(md5($user_id . time()), 0, 6));
                $referred_by = trim($_POST['referral'] ?? '');

                $stmt = $pdo->prepare("INSERT INTO students (user_id, dob, phone, address, referral_code, referred_by, admission_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$user_id, $dob ?: null, $phone, $address, $referral_code, $referred_by ?: null]);

                $pdo->commit();
                $success = "Application submitted successfully! Your username is: <strong>$username</strong>. Please wait for admin approval.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Something went wrong. Please try again.";
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
    <title>Student Application | TechnoHacks Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card animate-fade-in" style="max-width: 560px;">
        <div class="auth-logo">
            <i class="fas fa-microchip me-2"></i>TechnoHacks Solutions
        </div>
        <h4 class="text-center mb-4" style="font-weight: 700;">Student Application</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="border-radius: 10px; font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="border-radius: 10px; font-size: 0.9rem;">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
            <div class="text-center">
                <a href="../index.php" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i>Go to Login</a>
            </div>
        <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Full Name *</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="full_name" class="form-control border-start-0 ps-0" placeholder="Enter full name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Email *</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="Enter email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold">Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="Phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?php echo $_POST['dob'] ?? ''; ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Address</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Enter your address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password *</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Create password" required minlength="6">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Referral Code (optional)</label>
                <input type="text" name="referral" class="form-control" placeholder="If you have a referral code" value="<?php echo htmlspecialchars($_POST['referral'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="fas fa-paper-plane me-2"></i>Submit Application
            </button>
        </form>
        <?php endif; ?>

        <hr class="my-4">
        <div class="text-center small">
            <span class="text-muted">Already have an account?</span>
            <a href="../index.php" class="text-primary fw-bold text-decoration-none">Sign In</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
