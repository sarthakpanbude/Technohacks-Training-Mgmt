<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Admission Form";
$activePage = "visitors";

$inquiry_id = $_GET['id'] ?? null;
if (!$inquiry_id) {
    header("Location: visitors.php");
    exit;
}

// Fetch Inquiry Data
$stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = ?");
$stmt->execute([$inquiry_id]);
$inquiry = $stmt->fetch();

if (!$inquiry) {
    $stmt = $pdo->prepare("SELECT * FROM visitors WHERE id = ?");
    $stmt->execute([$inquiry_id]);
    $inquiry = $stmt->fetch();
    if ($inquiry) {
        $inquiry['course'] = $inquiry['course_interest'] ?? '';
        $inquiry['mobile'] = $inquiry['phone'] ?? '';
    }
}

if (!$inquiry) {
    header("Location: inquiries.php?error=Inquiry not found");
    exit;
}

$msg = "";
$error = "";

// Handle Final Admission Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_admission'])) {
    try {
        $pdo->beginTransaction();
        
        // Generate Student ID
        $year = date('Y');
        do {
            $rand = rand(1000, 9999);
            $student_id = "STU" . $year . $rand;
            $check = $pdo->prepare("SELECT id FROM students_basic WHERE student_id = ?");
            $check->execute([$student_id]);
        } while ($check->fetch());

        // File Uploads
        $photo_path = null;
        $id_path = null;
        $uploadDir = '../uploads/students/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (!empty($_FILES['photo']['name'])) {
            $photo_path = 'uploads/students/' . time() . '_photo_' . $_FILES['photo']['name'];
            move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photo_path);
        }
        if (!empty($_FILES['id_proof']['name'])) {
            $id_path = 'uploads/students/' . time() . '_id_' . $_FILES['id_proof']['name'];
            move_uploaded_file($_FILES['id_proof']['tmp_name'], '../' . $id_path);
        }

        // 1. Basic & Personal
        $stmt = $pdo->prepare("INSERT INTO students_basic (student_id, full_name, father_name, mother_name, dob, gender, email, course) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $_POST['full_name'], $_POST['father_name'], $_POST['mother_name'], $_POST['dob'], $_POST['gender'], $_POST['email'], $_POST['course']]);

        $stmt = $pdo->prepare("INSERT INTO personal_details (student_id, category, nationality, address, permanent_address, city, state, aadhaar_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $_POST['category'], $_POST['nationality'], $_POST['address'], $_POST['permanent_address'], $_POST['city'], $_POST['state'], $_POST['aadhaar_number']]);

        // 2. Education
        $stmt = $pdo->prepare("INSERT INTO education (student_id, qualification, college_name, passing_year, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $_POST['qualification'], $_POST['college_name'], $_POST['passing_year'], $_POST['edu_status']]);

        // 3. Documents
        if ($photo_path) {
            $pdo->prepare("INSERT INTO student_documents (student_id, doc_type, file_path) VALUES (?, 'photo', ?)")->execute([$student_id, $photo_path]);
        }
        if ($id_path) {
            $pdo->prepare("INSERT INTO student_documents (student_id, doc_type, file_path) VALUES (?, 'id_proof', ?)")->execute([$student_id, $id_path]);
        }

        // 4. Fees
        $paid = $_POST['paid_fee'] ?: 0;
        $total = $_POST['total_fee'] ?: 0;
        $pending = $total - $paid;
        $stmt = $pdo->prepare("INSERT INTO student_fees (student_id, total_fee, paid_fee, pending_fee, installments, payment_mode) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $total, $paid, $pending, $_POST['installments'], $_POST['payment_mode']]);

        // 5. Account & Students Sync
        $password = password_hash($_POST['mobile'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, 'student', ?, ?)");
        $stmt->execute([strtolower($student_id), $password, $_POST['email'], $_POST['full_name']]);
        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, dob, phone, address, admission_status, referral_id, course) VALUES (?, ?, ?, ?, ?, 'enrolled', ?, ?)");
        $stmt->execute([$user_id, $student_id, $_POST['dob'], $_POST['mobile'], $_POST['address'], (!empty($_POST['referral_id']) ? $_POST['referral_id'] : null), $_POST['course']]);
        $main_stu_id = $pdo->lastInsertId();

        // 6. Referral Bonus
        if (!empty($_POST['referral_id']) && $total > 0) {
            $bonus = $total * 0.10;
            $stmt = $pdo->prepare("INSERT INTO referral_bonus (referrer_id, referred_student_id, bonus_amount, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$_POST['referral_id'], $student_id, $bonus]);
        }

        // 7. Invoice
        if ($paid > 0) {
            $rcpt = "RCPT-" . date('Y') . "-" . str_pad($main_stu_id, 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO invoices (student_id, receipt_no, amount, payment_mode) VALUES (?, ?, ?, ?)");
            $stmt->execute([$main_stu_id, $rcpt, $paid, $_POST['payment_mode']]);
        }

        $pdo->prepare("UPDATE inquiries SET status = 'admitted' WHERE id = ?")->execute([$inquiry_id]);
        $pdo->prepare("UPDATE visitors SET status = 'converted' WHERE id = ?")->execute([$inquiry_id]);

        $pdo->commit();
        header("Location: admission_success.php?id=$student_id");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="mb-4">
        <h4 class="fw-bold mb-0">Admission Portal</h4>
        <p class="text-muted small">Streamlined enrollment for <span class="text-primary fw-bold"><?php echo htmlspecialchars($inquiry['name']); ?></span></p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger rounded-3 mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="stat-card bg-white rounded-4 shadow-sm border-0 overflow-hidden">
        <!-- Tab Navigation -->
        <ul class="nav nav-pills nav-fill bg-light p-2" id="admissionTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold py-3" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button">
                    <i class="fas fa-user-circle me-2"></i>1. Basic & Personal
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold py-3" id="edu-tab" data-bs-toggle="tab" data-bs-target="#edu" type="button">
                    <i class="fas fa-graduation-cap me-2"></i>2. Course & Education
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold py-3" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees" type="button">
                    <i class="fas fa-money-bill-wave me-2"></i>3. Fees & Payment
                </button>
            </li>
        </ul>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="tab-content p-4" id="admissionTabsContent">
                
                <!-- Tab 1: Basic & Personal -->
                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="full_name" class="form-control bg-light" value="<?php echo htmlspecialchars($inquiry['name']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control bg-light" value="<?php echo htmlspecialchars($inquiry['mobile']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email ID</label>
                            <input type="email" name="email" class="form-control bg-light" value="<?php echo htmlspecialchars($inquiry['email'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" placeholder="Enter father's name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" placeholder="Enter mother's name" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">DOB</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Aadhaar Number</label>
                            <input type="text" name="aadhaar_number" class="form-control" maxlength="12" placeholder="12 digit Aadhaar" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" name="city" class="form-control" placeholder="Enter city" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">State</label>
                            <input type="text" name="state" class="form-control" placeholder="Enter state" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Category</label>
                            <select name="category" class="form-select">
                                <option value="General">General</option>
                                <option value="OBC">OBC</option>
                                <option value="SC">SC</option>
                                <option value="ST">ST</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nationality</label>
                            <input type="text" name="nationality" class="form-control" value="Indian" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Address</label>
                            <textarea name="address" id="curr_addr" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold d-flex justify-content-between">
                                Permanent Address
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" id="sync_addr" onchange="syncAddress()">
                                    <label class="form-check-label text-muted small" for="sync_addr">Same as current</label>
                                </div>
                            </label>
                            <textarea name="permanent_address" id="perm_addr" class="form-control" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-primary px-4 rounded-pill" onclick="switchTab('edu-tab')">Next: Education <i class="fas fa-chevron-right ms-2"></i></button>
                    </div>
                </div>

                <!-- Tab 2: Course & Education -->
                <div class="tab-pane fade" id="edu" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Enrolling Course</label>
                            <input type="text" name="course" class="form-control bg-light" value="<?php echo htmlspecialchars($inquiry['course']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Qualification</label>
                            <input type="text" name="qualification" class="form-control" placeholder="e.g. BE Computer Science" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">College/University</label>
                            <input type="text" name="college_name" class="form-control" placeholder="Enter college name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Passing Year</label>
                            <input type="text" name="passing_year" class="form-control" placeholder="YYYY" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="edu_status" class="form-select">
                                <option value="Completed">Completed</option>
                                <option value="Appearing">Appearing</option>
                            </select>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label class="form-label small fw-bold">Passport Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label class="form-label small fw-bold">Aadhaar/ID Proof</label>
                            <input type="file" name="id_proof" class="form-control">
                        </div>
                    </div>
                    <div class="mt-5 d-flex justify-content-between">
                        <button type="button" class="btn btn-light px-4 rounded-pill" onclick="switchTab('basic-tab')">Back</button>
                        <button type="button" class="btn btn-primary px-4 rounded-pill" onclick="switchTab('fees-tab')">Next: Fees <i class="fas fa-chevron-right ms-2"></i></button>
                    </div>
                </div>

                <!-- Tab 3: Fees & Payment -->
                <div class="tab-pane fade" id="fees" role="tabpanel">
                    <?php
                    $c_stmt = $pdo->prepare("SELECT fees FROM courses WHERE course_name = ?");
                    $c_stmt->execute([$inquiry['course']]);
                    $c_fee = $c_stmt->fetchColumn() ?: 0;
                    ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Total Course Fee</label>
                            <input type="number" name="total_fee" id="total_fee" class="form-control bg-light fw-bold text-primary" value="<?php echo $c_fee; ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Paid Amount (Today)</label>
                            <input type="number" name="paid_fee" id="paid_fee" class="form-control" placeholder="0.00" oninput="calcFee()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Pending Amount</label>
                            <input type="number" name="pending_fee" id="pending_fee" class="form-control bg-light" value="<?php echo $c_fee; ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Installments</label>
                            <select name="installments" class="form-select">
                                <option value="1">Full Payment</option>
                                <option value="2">2 Installments</option>
                                <option value="3">3 Installments</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI/Online</option>
                                <option value="Card">Credit/Debit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Referral Student (Optional)</label>
                            <select name="referral_id" class="form-select">
                                <option value="">-- No Referrer --</option>
                                <?php
                                $referrers = $pdo->query("SELECT s.enrollment_no, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.admission_status = 'active' OR s.admission_status = 'enrolled' ORDER BY u.full_name ASC")->fetchAll();
                                foreach($referrers as $r): ?>
                                    <option value="<?php echo $r['enrollment_no']; ?>"><?php echo $r['enrollment_no']; ?> - <?php echo htmlspecialchars($r['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 border-top pt-4 text-center">
                        <div class="alert alert-info border-0 bg-light small mb-4">
                            <i class="fas fa-info-circle me-2"></i> Please review all details in the tabs above before final confirmation.
                        </div>
                        <button type="button" class="btn btn-light px-4 rounded-pill me-2" onclick="switchTab('edu-tab')">Back</button>
                        <button type="submit" name="confirm_admission" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm">
                            Confirm & Generate Admission <i class="fas fa-check-circle ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
function syncAddress() {
    const curr = document.getElementById('curr_addr');
    const perm = document.getElementById('perm_addr');
    if (document.getElementById('sync_addr').checked) {
        perm.value = curr.value;
    }
}

function calcFee() {
    const total = parseFloat(document.getElementById('total_fee').value) || 0;
    const paid = parseFloat(document.getElementById('paid_fee').value) || 0;
    document.getElementById('pending_fee').value = total - paid;
}

function switchTab(tabId) {
    const tab = document.getElementById(tabId);
    if (tab) {
        bootstrap.Tab.getInstance(tab) ? bootstrap.Tab.getInstance(tab).show() : new bootstrap.Tab(tab).show();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
