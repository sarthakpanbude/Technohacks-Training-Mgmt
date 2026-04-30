<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Full Admission Form";
$activePage = "visitors";

$inquiry_id = $_GET['id'] ?? null;
if (!$inquiry_id) {
    header("Location: visitors.php");
    exit;
}

// Fetch Inquiry Data (Try inquiries first, then visitors)
$stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = ?");
$stmt->execute([$inquiry_id]);
$inquiry = $stmt->fetch();

if (!$inquiry) {
    $stmt = $pdo->prepare("SELECT * FROM visitors WHERE id = ?");
    $stmt->execute([$inquiry_id]);
    $inquiry = $stmt->fetch();
    if ($inquiry) {
        $inquiry['course'] = $inquiry['course_interest'];
        $inquiry['mobile'] = $inquiry['phone'];
    }
}

if (!$inquiry) {
    header("Location: inquiries.php?error=Inquiry not found");
    exit;
}

$step = $_GET['step'] ?? 1;
$msg = "";

// Initialize session data for the form if not already there
session_start();
if (!isset($_SESSION['admission_data']) || (isset($_GET['new']) && $_GET['new'] == 1)) {
    $_SESSION['admission_data'] = [
        'inquiry_id' => $inquiry_id,
        'full_name' => $inquiry['name'],
        'email' => $inquiry['email'] ?? '',
        'mobile' => $inquiry['mobile'] ?? $inquiry['phone'] ?? '',
        'course' => $inquiry['course'] ?? $inquiry['course_interest'] ?? '',
        'college' => '', 
    ];
}

// Handle Form Submissions for each step
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['next_step'])) {
        // File Upload Handling for Step 4
        if ($step == 4 && isset($_FILES['photo'])) {
            $uploadDir = '../uploads/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            if (!empty($_FILES['photo']['name'])) {
                $photoName = time() . '_photo_' . $_FILES['photo']['name'];
                move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName);
                $_SESSION['uploaded_files']['photo'] = 'uploads/students/' . $photoName;
            }
            if (!empty($_FILES['id_proof']['name'])) {
                $idName = time() . '_id_' . $_FILES['id_proof']['name'];
                move_uploaded_file($_FILES['id_proof']['tmp_name'], $uploadDir . $idName);
                $_SESSION['uploaded_files']['id_proof'] = 'uploads/students/' . $idName;
            }
        }

        foreach ($_POST as $key => $value) {
            if ($key != 'next_step') {
                $_SESSION['admission_data'][$key] = $value;
            }
        }
        $next = $step + 1;
        header("Location: admit.php?id=$inquiry_id&step=$next");
        exit;
    }
    
    if (isset($_POST['prev_step'])) {
        $prev = $step - 1;
        header("Location: admit.php?id=$inquiry_id&step=$prev");
        exit;
    }

    if (isset($_POST['confirm_admission'])) {
        // Final Save Logic
        try {
            $pdo->beginTransaction();
            $data = $_SESSION['admission_data'];
            
            // Generate Auto Student ID
            $year = date('Y');
            do {
                $rand = rand(1000, 9999);
                $student_id = "STU" . $year . $rand;
                $check = $pdo->prepare("SELECT id FROM students_basic WHERE student_id = ?");
                $check->execute([$student_id]);
            } while ($check->fetch());

            // 1. Save Basic Details
            $stmt = $pdo->prepare("INSERT INTO students_basic (student_id, full_name, father_name, mother_name, dob, gender, email, course) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $data['full_name'], $data['father_name'], $data['mother_name'], $data['dob'], $data['gender'], $data['email'], $data['course']]);

            // 2. Save Personal Details
            $stmt = $pdo->prepare("INSERT INTO personal_details (student_id, category, nationality, address, permanent_address, city, state, aadhaar_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $data['category'], $data['nationality'], $data['address'], $data['permanent_address'], $data['city'], $data['state'], $data['aadhaar_number']]);

            // 3. Save Education
            $stmt = $pdo->prepare("INSERT INTO education (student_id, qualification, college_name, passing_year, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $data['qualification'], $data['college_name'], $data['passing_year'], $data['edu_status']]);

            // 4. Handle Documents (Assuming file names are stored in session from a temporary upload or handled here)
            // For simplicity in this demo, we'll assume files were uploaded in step 4
            if (isset($_SESSION['uploaded_files'])) {
                foreach ($_SESSION['uploaded_files'] as $type => $path) {
                    $stmt = $pdo->prepare("INSERT INTO student_documents (student_id, doc_type, file_path) VALUES (?, ?, ?)");
                    $stmt->execute([$student_id, $type, $path]);
                }
            }

            // 5. Save Fees
            $stmt = $pdo->prepare("INSERT INTO student_fees (student_id, total_fee, paid_fee, pending_fee, installments, next_installment_amount, next_installment_date, third_installment_amount, third_installment_date, payment_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $student_id, 
                $data['total_fee'], 
                $data['paid_fee'], 
                $data['pending_fee'], 
                $data['installments'], 
                $data['next_installment_amount'] ?? 0,
                $data['next_installment_date'] ?: null, 
                $data['third_installment_amount'] ?? 0,
                $data['third_installment_date'] ?: null,
                $data['payment_mode']
            ]);

            // 6. Create User Account (Check for duplicate email first)
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->execute([$data['email']]);
            $existingUser = $checkEmail->fetch();
            
            if (!$existingUser) {
                $username = strtolower($student_id);
                $password = password_hash($data['mobile'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, 'student', ?, ?)");
                $stmt->execute([$username, $password, $data['email'], $data['full_name']]);
                $user_id = $pdo->lastInsertId();
            } else {
                $user_id = $existingUser['id'];
            }

            // 7. Add to main students table for compatibility
            $stmt = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, dob, phone, address, admission_status) VALUES (?, ?, ?, ?, ?, 'enrolled')");
            $stmt->execute([$user_id, $student_id, $data['dob'], $data['mobile'], $data['address']]);
            $main_student_id = $pdo->lastInsertId();

            // 8. Create Initial Invoice (for the paid amount)
            if ($data['paid_fee'] > 0) {
                $receipt_no = "RCPT-" . date('Y') . "-" . str_pad($main_student_id, 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO invoices (student_id, receipt_no, amount, payment_mode) VALUES (?, ?, ?, ?)");
                $stmt->execute([$main_student_id, $receipt_no, $data['paid_fee'], $data['payment_mode']]);
            }

            // 9. Create Pending Installments
            if ($data['pending_fee'] > 0) {
                $inst_count = $data['installments'] - ($data['paid_fee'] > 0 ? 0 : 0); // Simplified
                $pending_per_inst = $data['pending_fee']; // For now assume one pending installment if not fully paid
                $stmt = $pdo->prepare("INSERT INTO installments (student_id, installment_no, amount, due_date, status) VALUES (?, 1, ?, ?, 'Pending')");
                $stmt->execute([$main_student_id, $pending_per_inst, $data['next_date'] ?: date('Y-m-d', strtotime('+1 month'))]);
            }

            // 10. Mark Inquiry as Admitted
            $pdo->prepare("UPDATE visitors SET status = 'converted' WHERE id = ?")->execute([$inquiry_id]);
            $pdo->prepare("UPDATE inquiries SET status = 'admitted' WHERE id = ?")->execute([$inquiry_id]);

            // 11. Set Premium Success Message
            $receipt_no = "RCPT-" . date('Y') . "-" . str_pad($main_student_id, 4, '0', STR_PAD_LEFT);
            $fee_display = number_format($data['paid_fee'], 2);
            $_SESSION['success_msg'] = "🎓 Admission Confirmed!\n\nWelcome to the TechnoHacks Elite Training System (ETS) 🚀\n\n━━━━━━━━━━━━━━━━━━━\n🧾 Receipt No : $receipt_no  \n💰 Course Fee : ₹$fee_display  \n━━━━━━━━━━━━━━━━━━━\n\n📢 Your enrollment is successfully activated.\n📚 Get ready to upgrade your skills and build your future with us.\n\n💡 TechnoHacks Team wishes you great success ahead!\n\n✨ Let's Start Your Journey →";

            $pdo->commit();
            unset($_SESSION['admission_data']);
            unset($_SESSION['uploaded_files']);
            header("Location: admission_success.php?id=$student_id");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Error: " . $e->getMessage();
        }
    }
}

// Final check if files are missing from session but present in request (fallback)
// This is now handled inside the POST next_step block, so this redundant block can be removed.

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="mb-4">
        <h4 class="fw-bold mb-0">Admission Process - Step <?php echo $step; ?> of 5</h4>
        <p class="text-muted small">Complete the CET-style admission form for <?php echo $inquiry['name']; ?>.</p>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-danger"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="progress mb-4" style="height: 10px; border-radius: 5px;">
        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($step/5)*100; ?>%;"></div>
    </div>

    <div class="stat-card bg-white rounded-4 shadow-sm p-4 border-0">
        <form action="" method="POST" enctype="multipart/form-data">
            
            <?php if ($step == 1): ?>
                <!-- Section 1: Basic Details -->
                <h5 class="fw-bold mb-4 text-primary border-bottom pb-2">Section 1: Basic Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Candidate Name (as per SSC)</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo $_SESSION['admission_data']['full_name']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email ID</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $_SESSION['admission_data']['email']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Father's Name</label>
                        <input type="text" name="father_name" class="form-control" value="<?php echo $_SESSION['admission_data']['father_name'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Mother's Name</label>
                        <input type="text" name="mother_name" class="form-control" value="<?php echo $_SESSION['admission_data']['mother_name'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo $_SESSION['admission_data']['dob'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($_SESSION['admission_data']['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($_SESSION['admission_data']['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($_SESSION['admission_data']['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 text-end">
                    <button type="submit" name="next_step" class="btn btn-primary px-4 rounded-pill">Next Step <i class="fas fa-chevron-right ms-2"></i></button>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- Section 2: Personal Details -->
                <h5 class="fw-bold mb-4 text-primary border-bottom pb-2">Section 2: Personal Details</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">City</label>
                        <input type="text" name="city" class="form-control" value="<?php echo $_SESSION['admission_data']['city'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">State</label>
                        <select name="state" class="form-select" required>
                            <option value="">Select State</option>
                            <?php
                            $states = ["Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat", "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal", "Andaman and Nicobar Islands", "Chandigarh", "Dadra and Nagar Haveli and Daman and Diu", "Delhi", "Jammu and Kashmir", "Ladakh", "Lakshadweep", "Puducherry"];
                            foreach($states as $st) {
                                $selected = ($_SESSION['admission_data']['state'] ?? '') == $st ? 'selected' : '';
                                echo "<option value='$st' $selected>$st</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Aadhaar Number</label>
                        <input type="text" name="aadhaar_number" class="form-control" value="<?php echo $_SESSION['admission_data']['aadhaar_number'] ?? ''; ?>" maxlength="12" pattern="\d{12}" title="Please enter 12 digit Aadhaar number" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nationality</label>
                        <select name="nationality" class="form-select" required>
                            <option value="Indian" <?php echo ($_SESSION['admission_data']['nationality'] ?? 'Indian') == 'Indian' ? 'selected' : ''; ?>>Indian</option>
                            <option value="Other" <?php echo ($_SESSION['admission_data']['nationality'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="General" <?php echo ($_SESSION['admission_data']['category'] ?? '') == 'General' ? 'selected' : ''; ?>>General</option>
                            <option value="OBC" <?php echo ($_SESSION['admission_data']['category'] ?? '') == 'OBC' ? 'selected' : ''; ?>>OBC</option>
                            <option value="SC" <?php echo ($_SESSION['admission_data']['category'] ?? '') == 'SC' ? 'selected' : ''; ?>>SC</option>
                            <option value="ST" <?php echo ($_SESSION['admission_data']['category'] ?? '') == 'ST' ? 'selected' : ''; ?>>ST</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Current Address</label>
                        <textarea name="address" id="current_address" class="form-control" rows="2" required><?php echo $_SESSION['admission_data']['address'] ?? ''; ?></textarea>
                    </div>
                    <div class="col-md-12 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="same_as_current" onchange="copyAddress()">
                            <label class="form-check-label small" for="same_as_current">Permanent Address same as Current Address</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Permanent Address</label>
                        <textarea name="permanent_address" id="permanent_address" class="form-control" rows="2" required><?php echo $_SESSION['admission_data']['permanent_address'] ?? ''; ?></textarea>
                    </div>
                </div>

                <script>
                function copyAddress() {
                    const current = document.getElementById('current_address');
                    const permanent = document.getElementById('permanent_address');
                    const checkbox = document.getElementById('same_as_current');
                    if (checkbox.checked) {
                        permanent.value = current.value;
                    }
                }
                </script>
                <div class="mt-4 d-flex justify-content-between">
                    <button type="submit" name="prev_step" class="btn btn-outline-secondary px-4 rounded-pill"><i class="fas fa-chevron-left me-2"></i> Previous</button>
                    <button type="submit" name="next_step" class="btn btn-primary px-4 rounded-pill">Next Step <i class="fas fa-chevron-right ms-2"></i></button>
                </div>

            <?php elseif ($step == 3): ?>
                <!-- Section 3: Course & Education -->
                <h5 class="fw-bold mb-4 text-primary border-bottom pb-2">Section 3: Course & Education</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Course Name</label>
                        <input type="text" name="course" class="form-control bg-light" value="<?php echo $_SESSION['admission_data']['course']; ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Course Level</label>
                        <select name="course_level" class="form-select" required>
                            <option value="Beginner" <?php echo ($_SESSION['admission_data']['course_level'] ?? '') == 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo ($_SESSION['admission_data']['course_level'] ?? '') == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo ($_SESSION['admission_data']['course_level'] ?? '') == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                </div>
                <h6 class="fw-bold mb-3">Previous Education Details</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Current / Completed Education</label>
                        <input type="text" name="qualification" class="form-control" value="<?php echo $_SESSION['admission_data']['qualification'] ?? ''; ?>" placeholder="e.g. B.Tech CS" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">College Name</label>
                        <input type="text" name="college_name" class="form-control" value="<?php echo $_SESSION['admission_data']['college_name'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Year of Passing</label>
                        <input type="text" name="passing_year" class="form-control" value="<?php echo $_SESSION['admission_data']['passing_year'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="edu_status" class="form-select" required>
                            <option value="Completed" <?php echo ($_SESSION['admission_data']['edu_status'] ?? '') == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Appearing" <?php echo ($_SESSION['admission_data']['edu_status'] ?? '') == 'Appearing' ? 'selected' : ''; ?>>Appearing</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex justify-content-between">
                    <button type="submit" name="prev_step" class="btn btn-outline-secondary px-4 rounded-pill"><i class="fas fa-chevron-left me-2"></i> Previous</button>
                    <button type="submit" name="next_step" class="btn btn-primary px-4 rounded-pill">Next Step <i class="fas fa-chevron-right ms-2"></i></button>
                </div>

            <?php elseif ($step == 4): ?>
                <!-- Section 4: Documents & Fees -->
                <h5 class="fw-bold mb-4 text-primary border-bottom pb-2">Section 4: Documents & Fees</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Passport Photo (required)</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <?php if(isset($_SESSION['uploaded_files']['photo'])): ?>
                            <small class="text-success">Photo uploaded: <?php echo basename($_SESSION['uploaded_files']['photo']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Aadhaar OR PAN (required)</label>
                        <input type="file" name="id_proof" class="form-control">
                        <?php if(isset($_SESSION['uploaded_files']['id_proof'])): ?>
                            <small class="text-success">ID Proof uploaded: <?php echo basename($_SESSION['uploaded_files']['id_proof']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                // Fetch Course Fee automatically
                $course_stmt = $pdo->prepare("SELECT fees FROM courses WHERE course_name = ?");
                $course_stmt->execute([$_SESSION['admission_data']['course']]);
                $course_fee = $course_stmt->fetchColumn() ?: 0;
                ?>
                <h6 class="fw-bold mb-3">Fee Management</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Total Fee</label>
                        <input type="number" name="total_fee" id="total_fee" class="form-control bg-light" value="<?php echo $_SESSION['admission_data']['total_fee'] ?? $course_fee; ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Paid Fee</label>
                        <input type="number" name="paid_fee" id="paid_fee" class="form-control" value="<?php echo $_SESSION['admission_data']['paid_fee'] ?? 0; ?>" required oninput="calcPending()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Pending Fee</label>
                        <input type="number" name="pending_fee" id="pending_fee" class="form-control bg-light" value="<?php echo $_SESSION['admission_data']['pending_fee'] ?? $course_fee; ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Installments</label>
                        <select name="installments" id="installments" class="form-select" required onchange="toggleInstallmentFields()">
                            <option value="1" <?php echo ($_SESSION['admission_data']['installments'] ?? '') == '1' ? 'selected' : ''; ?>>Full Payment (1)</option>
                            <option value="2" <?php echo ($_SESSION['admission_data']['installments'] ?? '') == '2' ? 'selected' : ''; ?>>2 Installments</option>
                            <option value="3" <?php echo ($_SESSION['admission_data']['installments'] ?? '') == '3' ? 'selected' : ''; ?>>3 Installments</option>
                        </select>
                    </div>
                    <!-- 2nd Installment Fields -->
                    <div class="col-md-4 installment-2-field" style="display: <?php echo ($_SESSION['admission_data']['installments'] ?? 1) >= 2 ? 'block' : 'none'; ?>;">
                        <label class="form-label small fw-bold">2nd Installment Amount</label>
                        <input type="number" name="next_installment_amount" id="next_installment_amount" class="form-control" value="<?php echo $_SESSION['admission_data']['next_installment_amount'] ?? 0; ?>">
                    </div>
                    <div class="col-md-4 installment-2-field" style="display: <?php echo ($_SESSION['admission_data']['installments'] ?? 1) >= 2 ? 'block' : 'none'; ?>;">
                        <label class="form-label small fw-bold">2nd Due Date</label>
                        <input type="date" name="next_installment_date" id="next_installment_date" class="form-control" value="<?php echo $_SESSION['admission_data']['next_installment_date'] ?? ''; ?>">
                    </div>
                    
                    <!-- 3rd Installment Fields -->
                    <div class="col-md-4 installment-3-field" style="display: <?php echo ($_SESSION['admission_data']['installments'] ?? 1) == 3 ? 'block' : 'none'; ?>;">
                        <label class="form-label small fw-bold">3rd Installment Amount</label>
                        <input type="number" name="third_installment_amount" id="third_installment_amount" class="form-control" value="<?php echo $_SESSION['admission_data']['third_installment_amount'] ?? 0; ?>">
                    </div>
                    <div class="col-md-4 installment-3-field" style="display: <?php echo ($_SESSION['admission_data']['installments'] ?? 1) == 3 ? 'block' : 'none'; ?>;">
                        <label class="form-label small fw-bold">3rd Due Date</label>
                        <input type="date" name="third_installment_date" id="third_installment_date" class="form-control" value="<?php echo $_SESSION['admission_data']['third_installment_date'] ?? ''; ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Payment Mode</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="Cash" <?php echo ($_SESSION['admission_data']['payment_mode'] ?? '') == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="UPI" <?php echo ($_SESSION['admission_data']['payment_mode'] ?? '') == 'UPI' ? 'selected' : ''; ?>>UPI</option>
                            <option value="Card" <?php echo ($_SESSION['admission_data']['payment_mode'] ?? '') == 'Card' ? 'selected' : ''; ?>>Card</option>
                            <option value="Net Banking" <?php echo ($_SESSION['admission_data']['payment_mode'] ?? '') == 'Net Banking' ? 'selected' : ''; ?>>Net Banking</option>
                        </select>
                    </div>
                </div>

                <script>
                function calcPending() {
                    const total = parseFloat(document.getElementById('total_fee').value) || 0;
                    const paid = parseFloat(document.getElementById('paid_fee').value) || 0;
                    const pending = total - paid;
                    document.getElementById('pending_fee').value = pending;
                    
                    const instCount = parseInt(document.getElementById('installments').value);
                    if (pending > 0) {
                        if (instCount === 2) {
                            document.getElementById('next_installment_amount').value = pending;
                            document.getElementById('third_installment_amount').value = 0;
                        } else if (instCount === 3) {
                            const half = Math.floor(pending / 2);
                            document.getElementById('next_installment_amount').value = half;
                            document.getElementById('third_installment_amount').value = pending - half;
                        }
                    }
                }
                
                function toggleInstallmentFields() {
                    const instCount = parseInt(document.getElementById('installments').value);
                    
                    document.querySelectorAll('.installment-2-field').forEach(el => {
                        el.style.display = instCount >= 2 ? 'block' : 'none';
                    });
                    
                    document.querySelectorAll('.installment-3-field').forEach(el => {
                        el.style.display = instCount === 3 ? 'block' : 'none';
                    });
                    
                    calcPending();
                }
                </script>
                <div class="mt-4 d-flex justify-content-between">
                    <button type="submit" name="prev_step" class="btn btn-outline-secondary px-4 rounded-pill"><i class="fas fa-chevron-left me-2"></i> Previous</button>
                    <button type="submit" name="next_step" class="btn btn-primary px-4 rounded-pill">Preview Application <i class="fas fa-chevron-right ms-2"></i></button>
                </div>

            <?php elseif ($step == 5): ?>
                <!-- Section 5: Preview -->
                <h5 class="fw-bold mb-4 text-primary border-bottom pb-2">Section 5: Preview & Confirm</h5>
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-bordered small">
                            <tr><th class="bg-light w-25">Full Name</th><td><?php echo $_SESSION['admission_data']['full_name']; ?></td></tr>
                            <tr><th class="bg-light">Father Name</th><td><?php echo $_SESSION['admission_data']['father_name']; ?></td></tr>
                            <tr><th class="bg-light">Mother Name</th><td><?php echo $_SESSION['admission_data']['mother_name']; ?></td></tr>
                            <tr><th class="bg-light">DOB / Gender</th><td><?php echo $_SESSION['admission_data']['dob'] . ' / ' . $_SESSION['admission_data']['gender']; ?></td></tr>
                            <tr><th class="bg-light">Course</th><td><?php echo $_SESSION['admission_data']['course']; ?></td></tr>
                            <tr><th class="bg-light">Total Fee</th><td>₹<?php echo number_format($_SESSION['admission_data']['total_fee'], 2); ?></td></tr>
                            <tr><th class="bg-light">Paid Fee</th><td class="text-success fw-bold">₹<?php echo number_format($_SESSION['admission_data']['paid_fee'], 2); ?></td></tr>
                            <tr><th class="bg-light">Pending</th><td class="text-danger fw-bold">₹<?php echo number_format($_SESSION['admission_data']['pending_fee'], 2); ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-4 text-center">
                        <?php if(isset($_SESSION['uploaded_files']['photo'])): ?>
                            <img src="../<?php echo $_SESSION['uploaded_files']['photo']; ?>" class="img-thumbnail rounded-4 mb-3" style="max-height: 200px;">
                        <?php endif; ?>
                        <div class="alert alert-info small">Please verify all details before confirming admission.</div>
                    </div>
                </div>
                <div class="mt-4 d-flex justify-content-between">
                    <button type="submit" name="prev_step" class="btn btn-outline-secondary px-4 rounded-pill"><i class="fas fa-chevron-left me-2"></i> Edit Details</button>
                    <button type="submit" name="confirm_admission" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm">Confirm Admission <i class="fas fa-check-circle ms-2"></i></button>
                </div>
            <?php endif; ?>

        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
