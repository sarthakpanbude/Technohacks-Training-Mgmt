<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "New Admission";
$activePage = "students";
$error = "";

// Fetch Active Students for Referral Dropdown
$referrers = $pdo->query("SELECT s.enrollment_no, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.admission_status = 'active' OR s.admission_status = 'enrolled'")->fetchAll();

// Fetch Courses for Dropdown
$courses = $pdo->query("SELECT id, course_name, fees FROM courses")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $address = trim($_POST['address']);
    $course_id = $_POST['course_id'] ?? '';
    $referral_id = trim($_POST['referral_id'] ?? '');

    if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($course_id)) {
        $error = "Please fill all required fields.";
    } else {
        $paid_amount = floatval($_POST['paid_amount'] ?? 0);
        $payment_mode = $_POST['payment_mode'] ?? 'Cash';
        
        $referrer = null;
        if (!empty($referral_id)) {
            $stmt = $pdo->prepare("SELECT enrollment_no FROM students WHERE enrollment_no = ?");
            $stmt->execute([$referral_id]);
            $referrer = $stmt->fetch();
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            $error = "Username or email already exists.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Get course details
                $stmt = $pdo->prepare("SELECT course_name, fees FROM courses WHERE id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch();
                $fee = $course['fees'] ?? 0;
                $course_name = $course['course_name'] ?? '';

                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, 'student', ?, ?)");
                $stmt->execute([$username, $hashed, $email, $full_name]);
                $user_id = $pdo->lastInsertId();

                $enrollment_no = 'TH' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                
                if ($referral_id == $enrollment_no) {
                    throw new Exception("Self referral not allowed.");
                }

                $referral_code = strtoupper(substr(md5($user_id . time()), 0, 6));

                $stmt = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, dob, phone, address, referral_code, referral_id, admission_status, course) VALUES (?, ?, ?, ?, ?, ?, ?, 'enrolled', ?)");
                $stmt->execute([$user_id, $enrollment_no, $dob, $phone, $address, $referral_code, (!empty($referral_id) ? $referral_id : null), $course_name]);
                $student_id = $pdo->lastInsertId();

                // 1. Record Fee Details
                $pending_fee = $fee - $paid_amount;
                $stmt = $pdo->prepare("INSERT INTO student_fees (student_id, total_fee, paid_fee, pending_fee, payment_mode) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$enrollment_no, $fee, $paid_amount, $pending_fee, $payment_mode]);

                // 2. Record Invoice/Payment
                if ($paid_amount > 0) {
                    $receipt_no = 'REC-' . time() . rand(10, 99);
                    $stmt = $pdo->prepare("INSERT INTO invoices (student_id, receipt_no, amount, payment_mode) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$student_id, $receipt_no, $paid_amount, $payment_mode]);
                }

                // 3. Bonus Calculation: 10% of course fee
                if ($referrer && $fee > 0) {
                    $bonus_amount = $fee * 0.10;
                    $stmt = $pdo->prepare("INSERT INTO referral_bonus (referrer_id, referred_student_id, bonus_amount, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->execute([$referrer['enrollment_no'], $enrollment_no, $bonus_amount]);
                }

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
                header("Location: students.php?msg=Student Admitted Successfully&receipt=" . ($receipt_no ?? ''));
                exit;
            } catch (Exception $e) {
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
            <p class="text-muted">Add a new student and calculate referral bonus automatically.</p>
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
                
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Select Course *</label>
                    <select name="course_id" id="course_id" class="form-select" required onchange="updateFee()">
                        <option value="" data-fee="0">-- Choose Course --</option>
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" data-fee="<?php echo $c['fees']; ?>" <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12 mt-4">
                    <h6 class="fw-bold text-primary border-bottom pb-2"><i class="fas fa-file-invoice-dollar me-2"></i>Fee & Payment Details</h6>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold">Total Course Fee</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="total_fee" id="total_fee" class="form-control bg-light" readonly value="<?php echo $_POST['total_fee'] ?? '0'; ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold">Initial Payment (Paid Amount) *</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="paid_amount" class="form-control" required value="<?php echo $_POST['paid_amount'] ?? '0'; ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold">Payment Mode *</label>
                    <select name="payment_mode" class="form-select" required>
                        <option value="Cash">Cash</option>
                        <option value="Online">Online Transfer</option>
                        <option value="UPI/GPay">UPI / GPay / PhonePe</option>
                        <option value="Bank Deposit">Bank Deposit</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>

                <div class="col-md-12 mt-4">
                    <h6 class="fw-bold text-success border-bottom pb-2"><i class="fas fa-users me-2"></i>Referral Program (Optional)</h6>
                </div>

                <div class="col-md-12">
                    <label class="form-label small fw-bold">Select Referrer Student</label>
                    <select name="referral_id" class="form-select">
                        <option value="">-- No Referrer (Direct Admission) --</option>
                        <?php foreach($referrers as $r): ?>
                            <option value="<?php echo $r['enrollment_no']; ?>" <?php echo (isset($_POST['referral_id']) && $_POST['referral_id'] == $r['enrollment_no']) ? 'selected' : ''; ?>>
                                <?php echo $r['enrollment_no']; ?> - <?php echo htmlspecialchars($r['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Referrer will automatically receive a 10% bonus of the total course fee.</small>
                </div>

                <div class="col-md-12 mt-4">
                    <h6 class="fw-bold text-muted border-bottom pb-2"><i class="fas fa-image me-2"></i>Documents</h6>
                </div>

                <div class="col-md-12">
                    <label class="form-label small fw-bold">Passport Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Full Address</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm"><i class="fas fa-user-check me-2"></i>Complete Admission</button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
function updateFee() {
    var select = document.getElementById('course_id');
    var fee = select.options[select.selectedIndex].getAttribute('data-fee');
    document.getElementById('total_fee').value = fee;
}
</script>

<?php include '../includes/footer.php'; ?>
