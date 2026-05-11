<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Student Profile";
$activePage = "students";

$id = $_GET['id'] ?? 0;
$student = $pdo->prepare("SELECT s.*, u.full_name as display_name, u.email, u.created_at as joined_at, r.full_name as referrer_name 
                           FROM students s 
                           LEFT JOIN users u ON s.user_id = u.id 
                           LEFT JOIN students sr ON s.referral_id = sr.enrollment_no
                           LEFT JOIN users r ON sr.user_id = r.id 
                           WHERE s.id = ?");
$student->execute([$id]);
$student = $student->fetch();

if (!$student) {
    header("Location: students.php?msg=Student Not Found");
    exit;
}

// Fetch enrollments
// Fetch Basic & Personal
$stmt = $pdo->prepare("SELECT * FROM students_basic WHERE student_id = ?");
$stmt->execute([$student['enrollment_no']]);
$basic = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM personal_details WHERE student_id = ?");
$stmt->execute([$student['enrollment_no']]);
$personal = $stmt->fetch() ?: [];

// Fetch Education
$stmt = $pdo->prepare("SELECT * FROM education WHERE student_id = ?");
$stmt->execute([$student['enrollment_no']]);
$education = $stmt->fetch() ?: [];

// Fetch Documents
$stmt = $pdo->prepare("SELECT * FROM student_documents WHERE student_id = ?");
$stmt->execute([$student['enrollment_no']]);
$documents = $stmt->fetchAll();

// Fetch enrollments (Joining through batches to get course info, but ignoring batch name)
$enrollments = $pdo->prepare("SELECT e.*, c.name as course_name FROM enrollments e JOIN batches b ON e.batch_id = b.id JOIN courses c ON b.course_id = c.id WHERE e.student_id = ?");
$enrollments->execute([$id]);
$enrollments = $enrollments->fetchAll();

// Fetch payments (Consolidated from payments and invoices)
$stmt = $pdo->prepare("SELECT amount, receipt_no, payment_date, 'Payment' as type FROM payments WHERE student_id = ? 
                       UNION ALL 
                       SELECT amount, receipt_no, payment_date, 'Admission' as type FROM invoices WHERE student_id = ? 
                       ORDER BY payment_date DESC");
$stmt->execute([$id, $id]);
$payments = $stmt->fetchAll();

$totalPaid = array_sum(array_column($payments, 'amount'));

// Fetch Fee Summary
$stmt = $pdo->prepare("SELECT * FROM student_fees WHERE student_id = ?");
$stmt->execute([$student['enrollment_no']]);
$feeSummary = $stmt->fetch();

// Fetch Installments
$stmt = $pdo->prepare("SELECT * FROM installments WHERE student_id = ? ORDER BY due_date ASC");
$stmt->execute([$id]);
$installmentsList = $stmt->fetchAll();

// Fetch Referral Info (Who referred this student)
$stmt = $pdo->prepare("
    SELECT rb.*, u.full_name as referrer_name, sf.pending_fee, s.admission_status
    FROM referral_bonuses rb
    JOIN students s ON rb.referrer_id = s.enrollment_no
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_fees sf ON rb.referred_id = sf.student_id
    WHERE rb.referred_id = ?
");
$stmt->execute([$student['enrollment_no']]);
$referredByBonus = $stmt->fetch();

// Fetch Referrals Made By This Student
$stmt = $pdo->prepare("
    SELECT rb.*, u.full_name as referred_name, sf.pending_fee, s.admission_status
    FROM referral_bonuses rb
    JOIN students s ON rb.referred_id = s.enrollment_no
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_fees sf ON s.enrollment_no = sf.student_id
    WHERE rb.referrer_id = ?
    ORDER BY rb.created_at DESC
");
$stmt->execute([$student['enrollment_no']]);
$myReferrals = $stmt->fetchAll();

include '../includes/header.php';
?>
<style>
    .stat-card.profile-card {
        transition: transform 0.3s ease;
    }
    .avatar-container img {
        transition: all 0.3s ease;
    }
    .avatar-container img:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
    }
    .profile-info-item {
        padding: 8px 0;
        border-bottom: 1px solid rgba(0,0,0,0.03);
    }
    .profile-info-item:last-child {
        border-bottom: none;
    }
    .profile-info-item i {
        width: 25px;
    }
    .x-small {
        font-size: 0.7rem;
    }
    .nav-pills .nav-link.active {
        background-color: #0d6efd;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2);
    }
    .nav-pills .nav-link {
        color: #6c757d;
        border: 1px solid transparent;
        margin: 0 2px;
        white-space: nowrap;
        font-size: 0.85rem;
        padding: 8px 15px !important;
    }
</style>
<?php
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="row align-items-end mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <h2 class="fw-bold mb-1">Student Profile</h2>
                <?php if (in_array($student['admission_status'], ['cancelled', 'refunded'])): ?>
                    <span class="badge bg-danger rounded-pill px-3 py-2 text-uppercase" style="letter-spacing: 1px;">
                        <i class="fas fa-times-circle me-1"></i> <?php echo $student['admission_status']; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="edit_student.php?id=<?php echo $id; ?>" class="btn btn-warning text-white rounded-pill px-4 shadow-sm">
                <i class="fas fa-edit me-2"></i>Edit Profile
            </a>
            <?php if (!empty($student['enrollment_no'])): ?>
                <a href="generate_form.php?id=<?php echo urlencode($student['enrollment_no']); ?>" target="_blank" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="fas fa-print me-2"></i>Print Form
                </a>
                <a href="generate_receipt.php?id=<?php echo urlencode($student['enrollment_no']); ?>" target="_blank" class="btn btn-success rounded-pill px-4 shadow-sm">
                    <i class="fas fa-receipt me-2"></i>Fees Receipt
                </a>
            <?php endif; ?>
            <a href="students.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-md-4">
            <div class="stat-card h-100 profile-card position-relative overflow-hidden">
                <div class="text-center mb-3">
                    <?php 
                    $photo_doc = array_filter($documents, fn($d) => $d['doc_type'] == 'photo');
                    $photo_path = !empty($photo_doc) ? '../' . reset($photo_doc)['file_path'] : null;
                    ?>
                    <div class="avatar-container mx-auto mb-3" style="width: 100px; height: 100px;">
                        <?php if ($photo_path && file_exists(__DIR__ . '/../' . reset($photo_doc)['file_path'])): ?>
                            <img src="<?php echo $photo_path; ?>" class="rounded-circle shadow-sm border" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm mx-auto"
                                style="width: 100px; height: 100px; font-size: 2.5rem; font-weight: bold;">
                                <?php echo strtoupper(substr($student['display_name'] ?? 'ST', 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($student['display_name']); ?></h5>
                </div>
                <hr>
                <div class="profile-info-item"><i class="fas fa-book text-muted me-2"></i>Course:
                    <strong><?php echo htmlspecialchars($student['course'] ?? 'No Course'); ?></strong></div>
                <hr class="my-2 opacity-25">
                <div class="profile-info-item"><i
                        class="fas fa-id-card text-muted me-2"></i>Student ID: <strong><?php echo $student['enrollment_no'] ?? 'N/A'; ?></strong>
                </div>
                <?php if (!empty($student['referrer_name'])): ?>
                <div class="profile-info-item"><i class="fas fa-user-friends text-muted me-2"></i>Referred by:
                    <strong><?php echo htmlspecialchars($student['referrer_name']); ?></strong></div>
                <?php endif; ?>
                <div class="profile-info-item mb-0"><i class="fas fa-share-alt text-muted me-2"></i>Your Referral Code:
                    <strong class="text-primary"><?php echo $student['referral_code'] ?? 'N/A'; ?></strong></div>

                <?php if ($feeSummary): ?>
                    <hr class="my-3 opacity-25">
                    <div class="p-3 bg-light rounded-4">
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted fw-bold">FEES STATUS</small>
                            <small class="fw-bold <?php echo $feeSummary['pending_fee'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $feeSummary['pending_fee'] > 0 ? 'Pending' : 'Cleared'; ?>
                            </small>
                        </div>
                        <div class="progress mb-2" style="height: 6px;">
                            <?php 
                            $percent = ($feeSummary['total_fee'] > 0) ? ($feeSummary['paid_fee'] / $feeSummary['total_fee']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="x-small text-muted">Paid: ₹<?php echo number_format($feeSummary['paid_fee'], 0); ?></span>
                            <span class="x-small text-muted text-end">Rem: ₹<?php echo number_format($feeSummary['pending_fee'], 0); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!in_array($student['admission_status'], ['cancelled', 'refunded'])): ?>
                    <hr class="my-3 opacity-25">
                    <button type="button" class="btn btn-outline-danger w-100 rounded-pill py-2 shadow-sm mb-2" data-bs-toggle="modal" data-bs-target="#cancelAdmissionModal">
                        <i class="fas fa-user-slash me-2"></i>Cancel Admission
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Details -->
        <div class="col-md-8">
            <div class="stat-card mb-4 border-0 shadow-sm p-3">
                <ul class="nav nav-pills mb-4 bg-light p-2 rounded-3 flex-nowrap overflow-x-auto custom-scrollbar" id="profileDetailTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold py-2" data-bs-toggle="tab" data-bs-target="#fee-info" type="button">
                            <i class="fas fa-money-check-alt me-2"></i>Installments
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold py-2" data-bs-toggle="tab" data-bs-target="#personal-info" type="button">
                            <i class="fas fa-user-tag me-2"></i>Personal Details
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold py-2" data-bs-toggle="tab" data-bs-target="#edu-info" type="button">
                            <i class="fas fa-graduation-cap me-2"></i>Education
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold py-2" data-bs-toggle="tab" data-bs-target="#docs-info" type="button">
                            <i class="fas fa-folder-open me-2"></i>Documents
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold py-2" data-bs-toggle="tab" data-bs-target="#referral-info" type="button">
                            <i class="fas fa-share-alt me-2"></i>Referrals
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-2" id="profileDetailTabsContent">
                    <!-- Personal Info -->
                    <div class="tab-pane fade" id="personal-info">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white">
                                    <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Gender</label>
                                    <span class="fw-bold text-dark"><i class="fas <?php echo ($basic['gender'] ?? '') == 'Male' ? 'fa-mars' : 'fa-venus'; ?> me-2 text-primary"></i><?php echo htmlspecialchars($basic['gender'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white">
                                    <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Birth Date</label>
                                    <span class="fw-bold text-dark"><i class="fas fa-birthday-cake me-2 text-primary"></i><?php echo $student['dob'] ? date('d M Y', strtotime($student['dob'])) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white">
                                    <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Email Address</label>
                                    <span class="fw-bold text-dark"><i class="fas fa-envelope me-2 text-primary"></i><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white">
                                    <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Phone Number</label>
                                    <span class="fw-bold text-dark"><i class="fas fa-phone me-2 text-primary"></i><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="p-3 border rounded-3 bg-white">
                                    <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Address</label>
                                    <span class="fw-medium text-dark"><i class="fas fa-map-marker-alt me-2 text-primary"></i><?php echo nl2br(htmlspecialchars($student['address'] ?: 'N/A')); ?></span>
                                </div>
                            </div>
                    </div>

                    <!-- Education -->
                    <div class="tab-pane fade" id="edu-info">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <div class="p-3 border rounded-3 bg-white h-100">
                                    <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">College/University</label>
                                    <span class="fw-bold h6 d-block mt-1 text-primary"><?php echo htmlspecialchars($education['college_name'] ?? 'Not Provided'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white h-100">
                                    <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Qualification</label>
                                    <span class="fw-bold h6 d-block mt-1 text-dark"><?php echo htmlspecialchars($education['qualification'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white">
                                    <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Passing Year</label>
                                    <span class="fw-bold h6 d-block mt-1 text-dark"><i class="fas fa-calendar-check me-2 text-primary"></i><?php echo htmlspecialchars($education['passing_year'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="tab-pane fade" id="docs-info">
                        <?php if (empty($documents)): ?>
                            <p class="text-muted small">No documents uploaded.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($documents as $doc): ?>
                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 bg-light text-center h-100 d-flex flex-column justify-content-between">
                                            <div>
                                                <i class="fas <?php echo $doc['doc_type'] == 'photo' ? 'fa-user-image' : 'fa-file-pdf'; ?> fa-2x mb-2 text-muted"></i>
                                                <h6 class="small fw-bold text-uppercase mb-1"><?php echo str_replace('_', ' ', $doc['doc_type']); ?></h6>
                                                <p class="x-small text-muted mb-3">Uploaded: <?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?></p>
                                            </div>
                                            <a href="../<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill">
                                                <i class="fas fa-external-link-alt me-1"></i> View Document
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Referrals -->
                    <div class="tab-pane fade" id="referral-info">
                        <!-- Who Referred This Student -->
                        <div class="mb-5">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Referred By</h6>
                            <?php if ($referredByBonus): ?>
                                <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($referredByBonus['referrer_name']); ?></div>
                                        <div class="text-muted small">Referrer ID: <?php echo $referredByBonus['referrer_id']; ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="x-small text-muted text-uppercase fw-bold mb-1">Referral Discount</div>
                                        <div class="fw-bold text-success h5 mb-0">₹<?php echo number_format($referredByBonus['discount_amount'], 2); ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small">This student did not join through a referral.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Referrals Made By This Student -->
                        <div>
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Referrals Made By This Student</h6>
                            <?php if (empty($myReferrals)): ?>
                                <p class="text-muted small">No one has joined using this student's referral code yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 x-small text-uppercase">Referred Student</th>
                                                <th class="border-0 x-small text-uppercase">Bonus Amount</th>
                                                <th class="border-0 x-small text-uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($myReferrals as $r): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold small"><?php echo htmlspecialchars($r['referred_name']); ?></div>
                                                        <div class="text-muted x-small"><?php echo $r['referred_id']; ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-primary small">₹<?php echo number_format($r['bonus_amount'], 2); ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <?php if ($r['status'] == 'Approved'): ?>
                                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2">
                                                                    <i class="fas fa-check-circle me-1"></i> Approved
                                                                </span>
                                                            <?php elseif ($r['status'] == 'Cancelled Due To Refund' || $r['admission_status'] == 'cancelled' || $r['admission_status'] == 'refunded'): ?>
                                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2">
                                                                    <i class="fas fa-times-circle me-1"></i> Cancelled
                                                                </span>
                                                            <?php else: ?>
                                                                <?php if ($r['pending_fee'] > 0): ?>
                                                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2">
                                                                        <i class="fas fa-clock me-1"></i> Full Payment Pending
                                                                    </span>
                                                                <?php endif; ?>

                                                                <?php if (strtotime($r['refund_expiry_date']) > time()): ?>
                                                                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2">
                                                                        <i class="fas fa-hourglass-half me-1"></i> Waiting Refund Period (Until <?php echo date('d M, Y', strtotime($r['refund_expiry_date'])); ?>)
                                                                    </span>
                                                                <?php endif; ?>

                                                                <?php if ($r['status'] == 'Pending' && $r['pending_fee'] <= 0 && strtotime($r['refund_expiry_date']) <= time()): ?>
                                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2">
                                                                        <i class="fas fa-spinner fa-spin me-1"></i> Processing Approval
                                                                    </span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Fee Info -->
                    <div class="tab-pane fade show active" id="fee-info">
                        <?php if (!$feeSummary): ?>
                            <p class="text-muted small">No fee structure defined.</p>
                        <?php else: ?>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 bg-white text-center h-100">
                                        <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Total Fee</label>
                                        <span class="fw-bold h5 text-dark">₹<?php echo number_format($feeSummary['total_fee'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 bg-white text-center h-100">
                                        <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Total Paid</label>
                                        <span class="fw-bold h5 text-success">₹<?php echo number_format($feeSummary['paid_fee'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 bg-white text-center h-100">
                                        <label class="x-small text-muted d-block text-uppercase fw-bold mb-1">Total Pending</label>
                                        <span class="fw-bold h5 text-danger">₹<?php echo number_format($feeSummary['pending_fee'], 2); ?></span>
                                    </div>
                                </div>
                            </div>

                            <h6 class="fw-bold mb-3"><i class="fas fa-list-ol me-2 text-primary"></i>Installment Plan</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0 small text-uppercase">No.</th>
                                            <th class="border-0 small text-uppercase">Amount</th>
                                            <th class="border-0 small text-uppercase">Due Date</th>
                                            <th class="border-0 small text-uppercase">Status</th>
                                            <th class="border-0 small text-uppercase">Paid Date</th>
                                            <th class="border-0 small text-uppercase">Reference</th>
                                            <th class="border-0 small text-uppercase text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($installmentsList as $inst): ?>
                                            <tr>
                                                <td><?php echo $inst['installment_no']; ?></td>
                                                <td>₹<?php echo number_format($inst['amount'], 2); ?></td>
                                                <td><?php echo date('d M Y', strtotime($inst['due_date'])); ?></td>
                                                <td>
                                                    <?php if ($inst['status'] == 'Paid'): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Paid</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($inst['status'] == 'Paid' && $inst['payment_date']): ?>
                                                        <small class="fw-bold text-success"><?php echo date('d M Y', strtotime($inst['payment_date'])); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo htmlspecialchars($inst['transaction_id'] ?? $inst['notes'] ?? '-'); ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($inst['status'] == 'Pending'): ?>
                                                        <a href="pay_installment.php?id=<?php echo $inst['id']; ?>&student_id=<?php echo $id; ?>" class="btn btn-sm btn-success rounded-pill px-3">
                                                            Pay Now
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="d-flex justify-content-center gap-2">
                                                            <a href="generate_receipt.php?id=<?php echo urlencode($student['enrollment_no']); ?>&inst_id=<?php echo $inst['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 ms-2">
                                                                <i class="fas fa-print me-1"></i> Receipt
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <!-- Payments -->
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-wallet me-2 text-success"></i>Payment History</h5>
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold">Total:
                        ₹<?php echo number_format($totalPaid, 2); ?></span>
                </div>
                <?php if (empty($payments)): ?>
                    <p class="text-muted">No payments recorded.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Receipt</th>
                                    <th class="border-0">Amount</th>
                                    <th class="border-0">Type</th>
                                    <th class="border-0">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?php echo $p['receipt_no']; ?></td>
                                        <td>₹<?php echo number_format($p['amount'], 2); ?></td>
                                        <td><span
                                                class="badge bg-info bg-opacity-10 text-info rounded-pill"><?php echo $p['type']; ?></span>
                                        </td>
                                        <td class="text-muted small"><?php echo date('d M Y', strtotime($p['payment_date'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Cancel Admission Modal -->
<div class="modal fade" id="cancelAdmissionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="fas fa-user-slash me-2"></i>Cancel Admission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="cancel_admission.php" method="POST">
                <div class="modal-body py-4">
                    <input type="hidden" name="student_id" value="<?php echo $id; ?>">
                    <input type="hidden" name="enrollment_no" value="<?php echo $student['enrollment_no']; ?>">
                    
                    <p class="text-muted mb-4">Are you sure you want to cancel the admission for <strong><?php echo htmlspecialchars($student['display_name']); ?></strong>? This action will update their status and payment records.</p>
                    
                    <div class="d-flex flex-column gap-3">
                        <label class="p-3 border rounded-3 cursor-pointer hover-bg-light transition">
                            <div class="d-flex align-items-center">
                                <input type="radio" name="cancel_type" value="without_refund" checked class="form-check-input me-3">
                                <div>
                                    <div class="fw-bold text-dark">Cancel Without Refund</div>
                                    <small class="text-muted">Status will be set to 'Cancelled'. Fees will be zeroed.</small>
                                </div>
                            </div>
                        </label>
                        
                        <label class="p-3 border rounded-3 cursor-pointer hover-bg-light transition">
                            <div class="d-flex align-items-center">
                                <input type="radio" name="cancel_type" value="with_refund" class="form-check-input me-3">
                                <div>
                                    <div class="fw-bold text-dark">Cancel With Refund</div>
                                    <small class="text-muted">Status will be set to 'Refunded'. All paid fees will be recorded as a refund.</small>
                                    
                                    <div class="mt-2 form-check" id="deleteOptionWrapper" style="display: none;">
                                        <input type="checkbox" name="delete_profile" id="deleteProfile" class="form-check-input">
                                        <label class="form-check-label small text-danger fw-bold" for="deleteProfile">
                                            Delete profile completely after refund
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="cancel_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const wrapper = document.getElementById('deleteOptionWrapper');
        wrapper.style.display = (this.value === 'with_refund') ? 'block' : 'none';
        if (this.value !== 'with_refund') {
            document.getElementById('deleteProfile').checked = false;
        }
    });
});
</script>

<style>
.cursor-pointer { cursor: pointer; }
.transition { transition: all 0.2s ease; }
.hover-bg-light:hover { background-color: #f8f9fa; border-color: #dee2e6 !important; }
</style>

<?php include '../includes/footer.php'; ?>
