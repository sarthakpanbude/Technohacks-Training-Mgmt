<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Edit Student Profile";
$activePage = "students";

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: students.php");
    exit;
}

// Fetch Main Student Data
$stmt = $pdo->prepare("SELECT s.*, u.full_name as display_name, u.email as user_email, u.id as user_id 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: students.php?error=Student not found");
    exit;
}

$enrollment_no = $student['enrollment_no'];

// Fetch Basic & Personal
$stmt = $pdo->prepare("SELECT * FROM students_basic WHERE student_id = ?");
$stmt->execute([$enrollment_no]);
$basic = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM personal_details WHERE student_id = ?");
$stmt->execute([$enrollment_no]);
$personal = $stmt->fetch() ?: [];

// Fetch Education
$stmt = $pdo->prepare("SELECT * FROM education WHERE student_id = ?");
$stmt->execute([$enrollment_no]);
$education = $stmt->fetch() ?: [];

// Fetch Documents
$stmt = $pdo->prepare("SELECT * FROM student_documents WHERE student_id = ?");
$stmt->execute([$enrollment_no]);
$documents = $stmt->fetchAll();

// Fetch Fee Summary
$stmt = $pdo->prepare("SELECT * FROM student_fees WHERE student_id = ?");
$stmt->execute([$enrollment_no]);
$feeSummary = $stmt->fetch();

// Fetch Installments
$stmt = $pdo->prepare("SELECT * FROM installments WHERE student_id = ? ORDER BY installment_no ASC");
$stmt->execute([$id]);
$currentInstallments = $stmt->fetchAll();

// Handle Update Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    try {
        $pdo->beginTransaction();

        // Check for Duplicate Student (Email or Mobile) - Excluding current record
        $email = $_POST['email'];
        $mobile = $_POST['mobile'];
        
        $check_duplicate = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_duplicate->execute([$email, $student['user_id']]);
        if ($check_duplicate->fetch()) {
            throw new Exception("Another student is already using this email address.");
        }

        $check_mobile = $pdo->prepare("SELECT id FROM students WHERE phone = ? AND id != ?");
        $check_mobile->execute([$mobile, $id]);
        if ($check_mobile->fetch()) {
            throw new Exception("Another student is already using this mobile number.");
        }
        
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $mobile = $_POST['mobile'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $address = $_POST['address'];
        $course = $_POST['course'];
        $total_fee = $_POST['total_fee'];
        
        // 1. Update Users Table
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $student['user_id']]);

        // 2. Update Students Table
        $stmt = $pdo->prepare("UPDATE students SET dob = ?, phone = ?, address = ?, course = ? WHERE id = ?");
        $stmt->execute([$dob, $mobile, $address, $course, $id]);

        // 3. Update Students Basic
        $stmt = $pdo->prepare("UPDATE students_basic SET full_name = ?, dob = ?, gender = ?, email = ?, course = ?, start_date = ?, duration = ? WHERE student_id = ?");
        $stmt->execute([$full_name, $dob, $gender, $email, $course, $_POST['start_date'], $_POST['duration'], $enrollment_no]);

        // 4. Update Personal Details
        $stmt = $pdo->prepare("UPDATE personal_details SET address = ?, permanent_address = ? WHERE student_id = ?");
        $stmt->execute([$_POST['address'], $_POST['address'], $enrollment_no]);

        // 5. Update Education
        $stmt = $pdo->prepare("UPDATE education SET qualification = ?, college_name = ?, passing_year = ? WHERE student_id = ?");
        $stmt->execute([$_POST['qualification'], $_POST['college_name'], $_POST['passing_year'], $enrollment_no]);

        // 6. Update Fee Summary
        $stmt = $pdo->prepare("UPDATE student_fees SET total_fee = ?, pending_fee = total_fee - paid_fee WHERE student_id = ?");
        $stmt->execute([$total_fee, $enrollment_no]);

        // 7. Handle Installments (Delete existing PENDING installments and recreate)
        if (isset($_POST['inst_amounts']) && is_array($_POST['inst_amounts'])) {
            // Delete only pending installments to avoid breaking history
            $stmt = $pdo->prepare("DELETE FROM installments WHERE student_id = ? AND status = 'Pending'");
            $stmt->execute([$id]);

            // Re-add installments from the form
            // First, get the max installment number for paid ones
            $stmt = $pdo->prepare("SELECT MAX(installment_no) FROM installments WHERE student_id = ? AND status = 'Paid'");
            $stmt->execute([$id]);
            $start_no = ($stmt->fetchColumn() ?: 0) + 1;

            $stmt = $pdo->prepare("INSERT INTO installments (student_id, installment_no, amount, due_date, status) VALUES (?, ?, ?, ?, 'Pending')");
            foreach ($_POST['inst_amounts'] as $i => $amt) {
                if ($amt > 0) {
                    $stmt->execute([$id, $start_no + $i, $amt, $_POST['inst_dates'][$i]]);
                }
            }
        }

        // 8. Handle File Uploads
        $uploadDir = '../uploads/students/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $doc_types = ['photo', 'id_proof', 'marksheet', 'other'];
        foreach($doc_types as $type) {
            if (!empty($_FILES[$type]['name'])) {
                $file_path = 'uploads/students/' . time() . '_' . $type . '_' . $_FILES[$type]['name'];
                if (move_uploaded_file($_FILES[$type]['tmp_name'], '../' . $file_path)) {
                    $check = $pdo->prepare("SELECT id FROM student_documents WHERE student_id = ? AND doc_type = ?");
                    $check->execute([$enrollment_no, $type]);
                    if ($check->fetch()) {
                        $pdo->prepare("UPDATE student_documents SET file_path = ? WHERE student_id = ? AND doc_type = ?")->execute([$file_path, $enrollment_no, $type]);
                    } else {
                        $pdo->prepare("INSERT INTO student_documents (student_id, doc_type, file_path) VALUES (?, ?, ?)")->execute([$enrollment_no, $type, $file_path]);
                    }
                }
            }
        }

        // 8. Update Referral Refund Expiry Date
        $days = ($total_fee < 6000) ? 7 : 14;
        $new_refund_expiry = date('Y-m-d', strtotime($_POST['start_date'] . " + $days days"));
        
        $stmt = $pdo->prepare("UPDATE referral_bonuses SET refund_expiry_date = ? WHERE referred_id = ? AND status != 'Approved' AND status != 'Cancelled Due To Refund'");
        $stmt->execute([$new_refund_expiry, $enrollment_no]);

        // 9. Update the first installment date if it was paid on the start date
        $stmt = $pdo->prepare("UPDATE installments SET due_date = ? WHERE student_id = ? AND installment_no = 1");
        $stmt->execute([$new_start_date, $id]);

        $pdo->commit();
        header("Location: view_student.php?id=$id&msg=Profile updated successfully");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating profile: " . $e->getMessage();
    }
}

$courses_list = $pdo->query("SELECT course_name, fees, course_type FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Edit Student Profile</h4>
            <p class="text-muted small">Comprehensive update for <span class="text-primary fw-bold"><?php echo htmlspecialchars($student['display_name']); ?></span> (<?php echo $enrollment_no; ?>)</p>
        </div>
        <a href="view_student.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary rounded-pill"><i class="fas fa-arrow-left me-2"></i>Back to Profile</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger rounded-3 mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" id="editStudentForm">
        <div class="stat-card bg-white rounded-4 shadow-sm border-0 p-0 overflow-hidden">
            <ul class="nav nav-tabs nav-justified border-bottom bg-light" id="editTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">
                        <i class="fas fa-user-circle me-2"></i>Personal Info
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tab-edu" type="button">
                        <i class="fas fa-graduation-cap me-2"></i>Education
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tab-course" type="button">
                        <i class="fas fa-book me-2"></i>Course & Docs
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold py-3 text-success" data-bs-toggle="tab" data-bs-target="#tab-fees" type="button">
                        <i class="fas fa-wallet me-2"></i>Fee & Installments
                    </button>
                </li>
            </ul>

            <div class="tab-content p-4">
                <!-- Tab 1: Personal -->
                <div class="tab-pane fade show active" id="tab-basic">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($basic['full_name'] ?? $student['display_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email ID <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($basic['email'] ?? $student['user_email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="dob" class="form-control" value="<?php echo $basic['dob'] ?? $student['dob'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="Male" <?php echo ($basic['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($basic['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($basic['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Current Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Education -->
                <div class="tab-pane fade" id="tab-edu">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Highest Qualification <span class="text-danger">*</span></label>
                            <input type="text" name="qualification" class="form-control" value="<?php echo htmlspecialchars($education['qualification'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Passing Year <span class="text-danger">*</span></label>
                            <input type="text" name="passing_year" class="form-control" value="<?php echo htmlspecialchars($education['passing_year'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">College/University Name <span class="text-danger">*</span></label>
                            <input type="text" name="college_name" class="form-control" value="<?php echo htmlspecialchars($education['college_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Course & Docs -->
                <div class="tab-pane fade" id="tab-course">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Program Type</label>
                            <select name="program_type" id="typeSelect" class="form-select" onchange="filterCourses()">
                                <option value="Training" <?php echo ($student['course_type'] ?? '') == 'Training' ? 'selected' : ''; ?>>Training Program</option>
                                <option value="Internship" <?php echo ($student['course_type'] ?? '') == 'Internship' ? 'selected' : ''; ?>>Internship Program</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Select Domain</label>
                            <select name="course" id="courseSelect" class="form-select" onchange="handleCourseChange()" required>
                                <option value="<?php echo htmlspecialchars($student['course']); ?>" selected><?php echo htmlspecialchars($student['course']); ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Course Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $basic['start_date'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Duration</label>
                            <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($basic['duration'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-12 mt-4">
                            <h6 class="small fw-bold border-bottom pb-2 mb-3">Update Documents (Optional)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label x-small fw-bold">Profile Photo</label>
                                    <input type="file" name="photo" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label x-small fw-bold">ID Proof</label>
                                    <input type="file" name="id_proof" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label x-small fw-bold">Marksheet</label>
                                    <input type="file" name="marksheet" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label x-small fw-bold">Other Doc</label>
                                    <input type="file" name="other" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Fees & Installments -->
                <div class="tab-pane fade" id="tab-fees">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <h6 class="fw-bold mb-3 small">Fee Configuration</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Final Payable Course Fee (₹)</label>
                                    <input type="number" name="total_fee" id="finalTotalFee" class="form-control fw-bold text-primary h5" value="<?php echo $feeSummary['total_fee'] ?? 0; ?>" oninput="calculateInstallments()">
                                    <small class="text-muted x-small">Note: Updating this will recalculate pending balance.</small>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small fw-bold">Paid Fee (So Far)</label>
                                    <input type="number" id="paidFee" class="form-control bg-white" value="<?php echo $feeSummary['paid_fee'] ?? 0; ?>" readonly>
                                    <small class="text-muted x-small">To add new payments, use the 'Pay Installment' option on profile.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <h6 class="fw-bold mb-3 small">Installment Plan Management</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">New Installment Count (Reset Plan)</label>
                                    <select name="installments" id="installmentCount" class="form-select" onchange="calculateInstallments()">
                                        <option value="0">Keep Current Plan</option>
                                        <option value="1">Lump Sum (1 Installment)</option>
                                        <option value="2">2 Installments</option>
                                        <option value="3">3 Installments</option>
                                        <option value="4">4 Installments</option>
                                    </select>
                                    <small class="text-danger x-small fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Selecting this will delete current pending installments!</small>
                                </div>
                                <div id="installmentBreakdown" style="display: none;">
                                    <h6 class="x-small fw-bold text-muted text-uppercase border-bottom pb-2">New Plan Breakdown</h6>
                                    <div id="installmentList" class="mt-3 small"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-light p-4 border-top text-center">
                <button type="submit" name="update_student" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm py-2">
                    <i class="fas fa-save me-2"></i>Update Entire Student Profile
                </button>
            </div>
        </div>
    </form>
</main>

<script>
const courses = <?php echo json_encode($courses_list); ?>;

function filterCourses() {
    const type = document.getElementById('typeSelect').value;
    const select = document.getElementById('courseSelect');
    const currentVal = "<?php echo $student['course']; ?>";
    
    select.innerHTML = '<option value="">Select Domain</option>';
    courses.forEach(c => {
        if (c.course_type === type) {
            const opt = document.createElement('option');
            opt.value = c.course_name;
            opt.textContent = c.course_name;
            opt.setAttribute('data-fee', c.fees);
            if (c.course_name === currentVal) opt.selected = true;
            select.appendChild(opt);
        }
    });
}

function handleCourseChange() {
    // We don't auto-reset the fee here to prevent accidental loss of custom pricing
    // unless the user clicks a specific button
}

function calculateInstallments() {
    const total = parseFloat(document.getElementById('finalTotalFee').value) || 0;
    const paid = parseFloat(document.getElementById('paidFee').value) || 0;
    const count = parseInt(document.getElementById('installmentCount').value) || 0;
    const breakdown = document.getElementById('installmentBreakdown');
    const list = document.getElementById('installmentList');

    if (count === 0) {
        breakdown.style.display = 'none';
        return;
    }

    breakdown.style.display = 'block';
    list.innerHTML = '';
    
    const remaining = total - paid;
    if (remaining <= 0) {
        list.innerHTML = '<div class="alert alert-success p-2 small mb-0">Balance is already cleared or zero.</div>';
        return;
    }

    const instAmt = (remaining / count).toFixed(2);
    
    for (let i = 1; i <= count; i++) {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-3 align-items-center';
        row.innerHTML = `
            <div class="col-md-1"><span class="fw-bold">#${i}</span></div>
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">₹</span>
                    <input type="number" name="inst_amounts[]" value="${instAmt}" class="form-control fw-bold text-dark" step="0.01">
                </div>
            </div>
            <div class="col-md-5">
                <input type="date" name="inst_dates[]" class="form-control form-control-sm" value="${getFutureDate(i)}">
            </div>
        `;
        list.appendChild(row);
    }
}

function getFutureDate(months) {
    const d = new Date();
    d.setMonth(d.getMonth() + months);
    return d.toISOString().split('T')[0];
}

// Initial filter
filterCourses();
</script>

<?php include '../includes/footer.php'; ?>
