<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "New Admission";
$activePage = "students";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $address = trim($_POST['address']);

    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $error = "Please fill all required fields.";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            $error = "Username or email already exists.";
        } else {
            try {
                $pdo->beginTransaction();
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, 'student', ?, ?)");
                $stmt->execute([$username, $hashed, $email, $full_name]);
                $user_id = $pdo->lastInsertId();

                $enrollment_no = 'TH' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                $referral_code = strtoupper(substr(md5($user_id . time()), 0, 6));

                $stmt = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, dob, phone, address, referral_code, admission_status) VALUES (?, ?, ?, ?, ?, ?, 'enrolled')");
                $stmt->execute([$user_id, $enrollment_no, $dob, $phone, $address, $referral_code]);

                // Handle Photo Upload
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                    $uploadDir = '../uploads/students/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    
                    $photoName = time() . '_photo_' . $_FILES['photo']['name'];
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName)) {
                        $photoPath = 'uploads/students/' . $photoName;
                        $stmt = $pdo->prepare("INSERT INTO student_documents (student_id, doc_type, file_path) VALUES (?, 'photo', ?)");
                        $stmt->execute([$enrollment_no, $photoPath]);
                    }
                }

                $pdo->commit();
                header("Location: students.php?msg=Student Added Successfully");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">New Student Admission</h2>
            <p class="text-muted">Add a new student to the system.</p>
        </div>
        <a href="students.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="border-radius: 12px;">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="stat-card">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Email *</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Username *</label>
                    <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?php echo $_POST['dob'] ?? ''; ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-bold">Passport Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Student</button>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
