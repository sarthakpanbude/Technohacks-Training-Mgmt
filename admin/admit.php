<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Admission Form";
$activePage = "visitors";

// Handle fresh admission request
if (isset($_GET['new']) && $_GET['new'] == 1) {
    $inquiry = [
        'id' => 0,
        'name' => '',
        'mobile' => '',
        'email' => '',
        'course' => ''
    ];
} else {
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

        $course_name = $_POST['course_type'] == 'Other' ? $_POST['other_course'] : $_POST['course'];

        // 1. Basic & Personal (Providing defaults for removed fields to prevent DB errors)
        $stmt = $pdo->prepare("INSERT INTO students_basic (student_id, full_name, father_name, mother_name, dob, gender, email, course) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $_POST['full_name'], '', '', $_POST['dob'], $_POST['gender'], $_POST['email'], $course_name]);

        $stmt = $pdo->prepare("INSERT INTO personal_details (student_id, category, nationality, address, permanent_address, city, state) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, 'General', 'Indian', $_POST['address'], $_POST['permanent_address'], $_POST['city'], $_POST['state']]);

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
        $total = $_POST['total_fee'];
        $paid = $_POST['paid_fee'] ?: 0;
        $pending = $total - $paid;
        $stmt = $pdo->prepare("INSERT INTO student_fees (student_id, total_fee, paid_fee, pending_fee, installments, payment_mode) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $total, $paid, $pending, $_POST['installments'], $_POST['payment_mode']]);

        // 5. Account & Students Sync
        $password = password_hash($_POST['mobile'], PASSWORD_DEFAULT);
        
        // Check if user already exists
        $check_user = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check_user->execute([$_POST['email']]);
        $existing_user = $check_user->fetch();

        if ($existing_user) {
            $user_id = $existing_user['id'];
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = 'student' WHERE id = ?");
            $stmt->execute([$_POST['full_name'], $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, 'student', ?, ?)");
            $stmt->execute([strtolower($student_id), $password, $_POST['email'], $_POST['full_name']]);
            $user_id = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, dob, phone, address, admission_status) VALUES (?, ?, ?, ?, ?, 'enrolled')");
        $stmt->execute([$user_id, $student_id, $_POST['dob'], $_POST['mobile'], $_POST['address']]);
        $main_stu_id = $pdo->lastInsertId();

        if ($paid > 0) {
            $rcpt = "RCPT-" . date('Y') . "-" . str_pad($main_stu_id, 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO invoices (student_id, receipt_no, amount, payment_mode) VALUES (?, ?, ?, ?)");
            $stmt->execute([$main_stu_id, $rcpt, $paid, $_POST['payment_mode']]);
        }

        if (isset($inquiry_id) && $inquiry_id > 0) {
            $pdo->prepare("UPDATE inquiries SET status = 'admitted' WHERE id = ?")->execute([$inquiry_id]);
            $pdo->prepare("UPDATE visitors SET status = 'converted' WHERE id = ?")->execute([$inquiry_id]);
        }

        $pdo->commit();
        header("Location: admission_success.php?id=$student_id");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

$courses_list = $pdo->query("SELECT course_name, fees FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Admission Portal</h4>
            <p class="text-muted small">
                <?php if($inquiry['name']): ?>
                    Streamlined enrollment for <span class="text-primary fw-bold"><?php echo htmlspecialchars($inquiry['name']); ?></span>
                <?php else: ?>
                    Creating a new student admission record
                <?php endif; ?>
            </p>
        </div>
        <a href="admit.php?new=1" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fas fa-plus me-2"></i>New Admission
        </a>
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
                <button class="nav-link fw-bold py-3" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs" type="button">
                    <i class="fas fa-file-alt me-2"></i>2. Course & Documentation
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold py-3" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees" type="button">
                    <i class="fas fa-money-bill-wave me-2"></i>3. Fees & Payment
                </button>
            </li>
        </ul>

        <form action="" method="POST" enctype="multipart/form-data" id="admissionForm">
            <div class="tab-content p-4" id="admissionTabsContent">
                
                <!-- Tab 1: Basic & Personal -->
                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($inquiry['name']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($inquiry['mobile']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email ID</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($inquiry['email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Qualification</label>
                            <input type="text" name="qualification" class="form-control" placeholder="e.g. BE Computer Science" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="edu_status" class="form-select" required>
                                <option value="Completed">Completed</option>
                                <option value="Pursuing">Pursuing</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Passing Year</label>
                            <input type="text" name="passing_year" class="form-control" placeholder="YYYY" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" name="city" class="form-control" placeholder="Enter city" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">State</label>
                            <input type="text" name="state" class="form-control" placeholder="Enter state" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">College/University Name</label>
                            <input type="text" name="college_name" class="form-control" placeholder="Enter current or last attended college" required>
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
                        <button type="button" class="btn btn-primary px-4 rounded-pill" onclick="switchTab('docs-tab')">Next: Course & Docs <i class="fas fa-chevron-right ms-2"></i></button>
                    </div>
                </div>

                <!-- Tab 2: Course & Documentation -->
                <div class="tab-pane fade" id="docs" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Program Type</label>
                            <select name="program_type" id="typeSelect" class="form-select border-primary" onchange="filterCourses()" required>
                                <option value="Training">Training Program</option>
                                <option value="Internship">Internship Program</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Select Domain</label>
                            <select name="course" id="courseSelect" class="form-select border-primary" onchange="handleCourseChange()" required>
                                <option value="">Select Domain</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="otherCourseCol" style="display: none;">
                            <label class="form-label small fw-bold text-primary">Enter Manual Course Name</label>
                            <input type="text" name="other_course" id="otherCourseInput" class="form-control border-primary" placeholder="Type course name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Course Level</label>
                            <select name="course_level" class="form-select border-primary">
                                <option value="Full Course">Full Course</option>
                                <option value="Advanced">Advanced</option>
                                <option value="Small Course">Small Course</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Course Start Date</label>
                            <input type="date" name="start_date" id="startDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Profile Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Aadhaar/ID Proof Document</label>
                            <input type="file" name="id_proof" class="form-control">
                        </div>
                        
                        <div class="col-12 mt-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="small fw-bold text-muted">Detected Course Fee:</span>
                                        <div id="feeStatus" class="small text-success mt-1"><i class="fas fa-check-circle me-1"></i> Auto-fetched from records</div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="input-group" style="max-width: 200px;">
                                            <span class="input-group-text bg-white border-end-0">₹</span>
                                            <input type="number" id="baseFeeDisplay" class="form-control border-start-0 fw-bold text-primary" readonly>
                                            <button type="button" class="btn btn-outline-secondary border-start-0" onclick="toggleFeeEdit()" title="Override Fee">
                                                <i class="fas fa-pen-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 d-flex justify-content-between">
                        <button type="button" class="btn btn-light px-4 rounded-pill" onclick="switchTab('basic-tab')">Back</button>
                        <button type="button" class="btn btn-primary px-4 rounded-pill" onclick="switchTab('fees-tab')">Next: Payment <i class="fas fa-chevron-right ms-2"></i></button>
                    </div>
                </div>

                <!-- Tab 3: Fees & Payment -->
                <div class="tab-pane fade" id="fees" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-4 rounded-4 h-100">
                                <h6 class="fw-bold mb-4">Course Pricing & Discounts</h6>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Standard Course Fee (₹)</label>
                                    <input type="number" id="stdFee" class="form-control bg-white" readonly>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Discount Type</label>
                                        <select id="discountType" class="form-select bg-white" onchange="calculateFinalFee()">
                                            <option value="none">No Discount</option>
                                            <option value="percent">Percentage (%)</option>
                                            <option value="fixed">Fixed Amount (₹)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Discount Value</label>
                                        <input type="number" id="discountValue" class="form-control bg-white" value="0" oninput="calculateFinalFee()">
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Discount Applied:</span>
                                        <span class="fw-bold text-danger" id="discountDisplay">-₹0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold h5 mb-0">Total Payable Fee:</span>
                                        <input type="hidden" name="total_fee" id="finalTotalFee">
                                        <span class="fw-bold h4 mb-0 text-primary" id="finalFeeDisplay">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-4 rounded-4 h-100">
                                <h6 class="fw-bold mb-4">Payment & Installment Plan</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Paid Today (₹)</label>
                                        <input type="number" name="paid_fee" id="paidFee" class="form-control bg-white fw-bold text-success" placeholder="0.00" oninput="calculateInstallments()">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Payment Mode</label>
                                        <select name="payment_mode" class="form-select bg-white">
                                            <option value="Cash">Cash</option>
                                            <option value="UPI">UPI/Online</option>
                                            <option value="Card">Credit/Debit Card</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="form-label small fw-bold">Installment Options</label>
                                        <select name="installments" id="installmentCount" class="form-select bg-white" onchange="calculateInstallments()">
                                            <option value="1">One-time Payment</option>
                                            <option value="2">2 Installments</option>
                                            <option value="3">3 Installments</option>
                                            <option value="4">4 Installments</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-4" id="installmentBreakdown" style="display: none;">
                                    <h6 class="small fw-bold text-muted text-uppercase border-bottom pb-2">Plan Breakdown</h6>
                                    <div id="installmentList" class="mt-3 small">
                                        <!-- Dynamic installments list -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 border-top pt-4 text-center">
                        <div class="alert alert-info border-0 bg-light small mb-4 shadow-sm">
                            <i class="fas fa-info-circle me-2"></i> Review the plan with student before generating admission.
                        </div>
                        <button type="button" class="btn btn-light px-4 rounded-pill me-2" onclick="switchTab('docs-tab')">Back</button>
                        <button type="submit" name="confirm_admission" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm hvr-grow">
                            Finish & Generate Admission <i class="fas fa-check-circle ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
const allCourses = <?php 
    $courses = $pdo->query("SELECT course_name, course_type, fees FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($courses);
?>;

function filterCourses() {
    const type = document.getElementById('typeSelect').value;
    const select = document.getElementById('courseSelect');
    const currentCourse = "<?php echo addslashes($inquiry['course']); ?>";
    
    select.innerHTML = '<option value="">Select Domain</option>';
    
    // Map Course Type to DB equivalent
    const dbType = type;

    allCourses.forEach(c => {
        if (c.course_type === dbType) {
            const opt = document.createElement('option');
            opt.value = c.course_name;
            opt.dataset.fee = c.fees;
            opt.textContent = c.course_name;
            if (c.course_name === currentCourse) opt.selected = true;
            select.appendChild(opt);
        }
    });

    const otherOpt = document.createElement('option');
    otherOpt.value = 'Other';
    otherOpt.textContent = 'Other (Enter manually)';
    select.appendChild(otherOpt);

    handleCourseChange();
}

function syncAddress() {
    const curr = document.getElementById('curr_addr');
    const perm = document.getElementById('perm_addr');
    if (document.getElementById('sync_addr').checked) {
        perm.value = curr.value;
    }
}

function handleCourseChange() {
    const select = document.getElementById('courseSelect');
    const otherCol = document.getElementById('otherCourseCol');
    const display = document.getElementById('baseFeeDisplay');
    const stdFee = document.getElementById('stdFee');
    const feeStatus = document.getElementById('feeStatus');
    
    if (select.value === 'Other') {
        otherCol.style.display = 'block';
        display.readOnly = false;
        display.value = 0;
        stdFee.value = 0;
        feeStatus.innerHTML = '<i class="fas fa-edit me-1 text-warning"></i> Manual entry mode';
        feeStatus.classList.replace('text-success', 'text-warning');
    } else {
        otherCol.style.display = 'none';
        const fee = select.options[select.selectedIndex].getAttribute('data-fee') || 0;
        display.value = fee;
        display.readOnly = true;
        stdFee.value = fee;
        feeStatus.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i> Auto-fetched from records';
        feeStatus.classList.replace('text-warning', 'text-success');
    }
    calculateFinalFee();
}

function toggleFeeEdit() {
    const display = document.getElementById('baseFeeDisplay');
    const feeStatus = document.getElementById('feeStatus');
    display.readOnly = !display.readOnly;
    if (!display.readOnly) {
        display.focus();
        feeStatus.innerHTML = '<i class="fas fa-edit me-1 text-warning"></i> Manual override active';
        feeStatus.classList.replace('text-success', 'text-warning');
    } else {
        feeStatus.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i> Re-locked to fetched value';
        feeStatus.classList.replace('text-warning', 'text-success');
    }
}

document.getElementById('baseFeeDisplay').addEventListener('input', function() {
    document.getElementById('stdFee').value = this.value;
    calculateFinalFee();
});

function calculateFinalFee() {
    const std = parseFloat(document.getElementById('stdFee').value) || 0;
    const discType = document.getElementById('discountType').value;
    const discVal = parseFloat(document.getElementById('discountValue').value) || 0;
    let discount = 0;

    if (discType === 'percent') {
        discount = (std * discVal) / 100;
    } else if (discType === 'fixed') {
        discount = discVal;
    }

    const final = Math.max(0, std - discount);
    document.getElementById('discountDisplay').textContent = '-₹' + discount.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('finalFeeDisplay').textContent = '₹' + final.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('finalTotalFee').value = final;
    calculateInstallments();
}

function calculateInstallments() {
    const total = parseFloat(document.getElementById('finalTotalFee').value) || 0;
    const paid = parseFloat(document.getElementById('paidFee').value) || 0;
    const count = parseInt(document.getElementById('installmentCount').value);
    const container = document.getElementById('installmentBreakdown');
    const list = document.getElementById('installmentList');
    const balance = total - paid;

    if (count > 1 && balance > 0) {
        container.style.display = 'block';
        list.innerHTML = '';
        
        // Paid row (today)
        list.innerHTML += `<div class="d-flex justify-content-between mb-2">
            <span><strong>Today:</strong> Admission Payment</span>
            <span class="text-success fw-bold">₹${paid.toLocaleString()}</span>
        </div>`;

        const instAmt = (balance / (count - 1)).toFixed(2);
        const startDate = new Date(document.getElementById('startDate').value);

        for (let i = 1; i < count; i++) {
            const dueDate = new Date(startDate);
            dueDate.setMonth(startDate.getMonth() + i);
            const dateStr = dueDate.toISOString().split('T')[0];

            list.innerHTML += `
            <div class="installment-row border-top pt-3 mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <label class="x-small fw-bold text-muted mb-1">INST. #${i} DUE DATE</label>
                        <input type="date" name="inst_dates[]" class="form-control form-control-sm" value="${dateStr}">
                    </div>
                    <div class="col-md-6">
                        <label class="x-small fw-bold text-muted mb-1">INST. #${i} AMOUNT (₹)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">₹</span>
                            <input type="number" name="inst_amounts[]" class="form-control border-start-0 fw-bold" value="${instAmt}" oninput="validateInstallmentTotal()">
                        </div>
                    </div>
                </div>
            </div>`;
        }
        list.innerHTML += `<div id="instTotalWarning" class="alert alert-warning py-2 mt-2 x-small" style="display:none;">
            <i class="fas fa-exclamation-triangle me-1"></i> Sum of installments doesn't match balance!
        </div>`;
    } else {
        container.style.display = 'none';
    }
}

function validateInstallmentTotal() {
    const total = parseFloat(document.getElementById('finalTotalFee').value) || 0;
    const paid = parseFloat(document.getElementById('paidFee').value) || 0;
    const balance = total - paid;
    
    const amounts = document.getElementsByName('inst_amounts[]');
    let sum = 0;
    amounts.forEach(input => sum += parseFloat(input.value) || 0);
    
    const warning = document.getElementById('instTotalWarning');
    if (Math.abs(sum - balance) > 0.01) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

function switchTab(tabId) {
    const tabEl = document.getElementById(tabId);
    if (tabEl) {
        bootstrap.Tab.getInstance(tabEl) ? bootstrap.Tab.getInstance(tabEl).show() : new bootstrap.Tab(tabEl).show();
    }
}

// Initial call
document.addEventListener('DOMContentLoaded', () => {
    // Determine initial program type based on inquiry course if possible
    const currentCourse = "<?php echo addslashes($inquiry['course']); ?>";
    const found = allCourses.find(c => c.course_name === currentCourse);
    if (found) {
        const typeSelect = document.getElementById('typeSelect');
        typeSelect.value = found.course_type;
    }
    filterCourses();
});
</script>

<?php include '../includes/footer.php'; ?>
