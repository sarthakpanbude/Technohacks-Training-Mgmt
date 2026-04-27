<?php
session_start();
require_once '../config/db.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $dob = $_POST['dob'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    try {
        $pdo->beginTransaction();

        // 1. Create User
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, 'student', ?, ?)");
        $stmt->execute([$username, $password, $email, $full_name]);
        $userId = $pdo->lastInsertId();

        // 2. Create Student Profile
        $referral_code = "TH" . strtoupper(substr(md5(uniqid()), 0, 6));
        $stmt2 = $pdo->prepare("INSERT INTO students (user_id, dob, phone, address, referral_code) VALUES (?, ?, ?, ?, ?)");
        $stmt2->execute([$userId, $dob, $phone, $address, $referral_code]);

        $pdo->commit();
        $success = "Application submitted successfully! You can now login.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Now | TechnoHacks Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">

    <div class="auth-card animate-fade-in" style="max-width: 600px;">
        <div class="auth-logo">
            <img src="../assets/img/logo.png" alt="TechnoHacks" style="max-height: 80px; width: auto; margin-bottom: 1rem;">
        </div>
        <h4 class="text-center mb-4" style="font-weight: 700;">Student Admission Form</h4>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="text-center"><a href="index.php" class="btn btn-primary">Go to Login</a></div>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Choose Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Create Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="col-12 mb-4">
                        <label class="form-label small fw-bold">Complete Address</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Submit Application</button>
                <div class="text-center small">
                    <span class="text-muted">Already have an account?</span> 
                    <a href="index.php" class="text-primary fw-bold text-decoration-none">Sign In</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
