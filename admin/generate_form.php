<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$student_id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT sb.*, pd.*, e.*, sf.*, s.phone FROM students_basic sb 
                      LEFT JOIN personal_details pd ON sb.student_id = pd.student_id 
                      LEFT JOIN education e ON sb.student_id = e.student_id 
                      LEFT JOIN student_fees sf ON sb.student_id = sf.student_id 
                      LEFT JOIN students s ON sb.student_id = s.enrollment_no
                      WHERE sb.student_id = ?");
$stmt->execute([$student_id]);
$s = $stmt->fetch();
if (!$s) exit("Not found");

$doc_stmt = $pdo->prepare("SELECT file_path FROM student_documents WHERE student_id = ? AND doc_type = 'photo'");
$doc_stmt->execute([$student_id]);
$photo = $doc_stmt->fetchColumn();

// Fallback to user profile pic if not found in documents
if (!$photo && isset($s['email'])) {
    $user_stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE email = ?");
    $user_stmt->execute([$s['email']]);
    $user_pic = $user_stmt->fetchColumn();
    if ($user_pic && $user_pic != 'default.png') {
        $photo = 'uploads/profiles/' . $user_pic;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admission Form - <?php echo htmlspecialchars($student_id); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 13px; font-family: 'Arial', sans-serif; }
        .receipt-container { border: 2px solid #333; padding: 30px; border-radius: 10px; max-width: 800px; margin: auto; }
        .logo { height: 60px; }
        .signature { margin-top: 50px; border-top: 1px solid #333; display: inline-block; width: 200px; }
        .table-details th { width: 30%; background-color: #f8f9fa; }
        .table-details th, .table-details td { padding: 6px 10px; border: 1px solid #dee2e6; }
        @media print { .no-print { display: none !important; } .receipt-container { border: none; padding: 0; } }
    </style>
</head>
<body class="bg-light p-4">
    <div class="receipt-container bg-white shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <img src="../assets/img/logo.png" class="logo">
            <div class="text-end">
                <h4 class="fw-bold mb-0" style="color: #0d6efd;">TechnoHacks Solutions</h4>
                <p class="text-muted small mb-0">Training Institute & Placement Cell</p>
                <p class="small mb-0">ISO 9001:2015 Certified</p>
            </div>
        </div>
        
        <h5 class="text-center bg-dark text-white p-2 mb-4 fw-bold rounded">APPLICATION FOR ADMISSION</h5>
        
        <div class="row mb-4">
            <div class="col-9">
                <table class="table table-sm table-details mb-0">
                    <tr><th>Enrollment No</th><td><strong><?php echo htmlspecialchars($s['student_id']); ?></strong></td></tr>
                    <tr><th>Candidate Name</th><td><?php echo htmlspecialchars($s['full_name']); ?></td></tr>
                    <tr><th>Course Selected</th><td><strong><?php echo htmlspecialchars($s['course']); ?></strong></td></tr>
                    <tr><th>Date of Birth</th><td><?php echo $s['dob'] ? date('d M, Y', strtotime($s['dob'])) : 'N/A'; ?></td></tr>
                    <tr><th>Gender</th><td><?php echo htmlspecialchars($s['gender'] ?? 'N/A'); ?></td></tr>
                </table>
            </div>
            <div class="col-3 text-center">
                <?php if ($photo && file_exists('../' . $photo)): ?>
                    <img src="../<?php echo htmlspecialchars($photo); ?>" alt="Student Photo" style="width: 120px; height: 150px; object-fit: cover; border: 2px solid #ddd; padding: 3px; border-radius: 4px;">
                <?php else: ?>
                    <div class="border d-flex align-items-center justify-content-center text-muted" style="width: 120px; height: 150px; margin: auto; background-color: #f8f9fa;">PHOTO</div>
                <?php endif; ?>
            </div>
        </div>
        
        <h6 class="fw-bold bg-light p-2 border mb-3">Personal & Contact Details</h6>
        <table class="table table-sm table-details mb-4">
            <tr><th>Father's Name</th><td><?php echo htmlspecialchars($s['father_name'] ?? 'N/A'); ?></td><th>Mother's Name</th><td><?php echo htmlspecialchars($s['mother_name'] ?? 'N/A'); ?></td></tr>
            <tr><th>Email ID</th><td><?php echo htmlspecialchars($s['email'] ?? 'N/A'); ?></td><th>Mobile No</th><td><?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></td></tr>
            <tr><th>Aadhaar Number</th><td><?php echo htmlspecialchars($s['aadhaar_number'] ?? 'N/A'); ?></td><th>Category</th><td><?php echo htmlspecialchars($s['category'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($s['nationality'] ?? 'Indian'); ?>)</td></tr>
            <tr><th>Current Address</th><td colspan="3"><?php echo htmlspecialchars($s['address'] ?? 'N/A'); ?>, <?php echo htmlspecialchars($s['city'] ?? ''); ?>, <?php echo htmlspecialchars($s['state'] ?? ''); ?></td></tr>
            <tr><th>Permanent Address</th><td colspan="3"><?php echo htmlspecialchars($s['permanent_address'] ?? 'N/A'); ?></td></tr>
        </table>

        <h6 class="fw-bold bg-light p-2 border mb-3">Academic Details</h6>
        <table class="table table-sm table-details mb-4">
            <tr><th>Highest Qualification</th><td><?php echo htmlspecialchars($s['qualification'] ?? 'N/A'); ?></td><th>Status</th><td><?php echo htmlspecialchars($s['status'] ?? 'N/A'); ?></td></tr>
            <tr><th>College/University</th><td colspan="3"><?php echo htmlspecialchars($s['college_name'] ?? 'N/A'); ?> (Passing Year: <?php echo htmlspecialchars($s['passing_year'] ?? 'N/A'); ?>)</td></tr>
        </table>
        
        <h6 class="fw-bold bg-light p-2 border mb-3">Fee Information</h6>
        <table class="table table-sm table-details mb-4">
            <tr><th>Total Course Fee</th><td>₹<?php echo number_format($s['total_fee'] ?? 0, 2); ?></td><th>Paid Amount</th><td>₹<?php echo number_format($s['paid_fee'] ?? 0, 2); ?></td></tr>
            <tr><th>Pending Balance</th><td><strong class="text-danger">₹<?php echo number_format($s['pending_fee'] ?? 0, 2); ?></strong></td><th>Payment Mode</th><td><?php echo htmlspecialchars($s['payment_mode'] ?? 'N/A'); ?></td></tr>
            <tr><th>Installment Plan</th><td colspan="3"><?php echo htmlspecialchars($s['installments'] ?? '1'); ?> Installment(s)</td></tr>
        </table>

        <div class="mt-4 border p-3 rounded bg-light small">
            <strong>Declaration:</strong> I hereby declare that all the information provided by me in this application is true and correct to the best of my knowledge and belief. I agree to abide by the rules and regulations of the institute.
        </div>
        
        <div class="mt-5 d-flex justify-content-between align-items-end">
            <div class="text-center">
                <div class="signature" style="width: 150px; border-bottom: 1px solid #333; margin-bottom: 5px;"></div>
                <p class="small mt-1 fw-bold">Student's Signature</p>
                <p class="small text-muted">Date: <?php echo date('d M, Y'); ?></p>
            </div>
            <div class="text-start">
                <p class="mb-1 small text-muted">Authorized By,</p>
                <div class="mt-2 mb-1">
                    <img src="../assets/img/signature.png" alt="Signature" style="max-height: 50px; display: block;" onerror="this.style.display='none'">
                    <div style="width: 150px; border-bottom: 1px solid #333; margin-bottom: 5px;"></div>
                </div>
                <h6 class="fw-bold mb-0">Sandip Gavit</h6>
                <p class="small mb-0 text-muted">Founder & CEO</p>
                <p class="small mb-0 text-muted">TechnoHacks Solutions</p>
            </div>
        </div>
        
        <div class="mt-5 text-center no-print">
            <button onclick="window.print()" class="btn btn-primary px-4 fw-bold shadow-sm"><i class="fas fa-print me-2"></i> Print Admission Form</button>
            <a href="students.php" class="btn btn-outline-secondary ms-2">Back to Students</a>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
