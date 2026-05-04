<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Issue Certificate";
$activePage = "students";

$enrollment_id = $_GET['enrollment_id'] ?? 0;
$msg = "";
$error = "";

// Fetch enrollment details
$stmt = $pdo->prepare("SELECT e.*, s.id as student_id, u.full_name, c.name as course_name, b.batch_name 
                       FROM enrollments e 
                       JOIN students s ON e.student_id = s.id 
                       JOIN users u ON s.user_id = u.id 
                       JOIN batches b ON e.batch_id = b.id 
                       JOIN courses c ON b.course_id = c.id 
                       WHERE e.id = ?");
$stmt->execute([$enrollment_id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    header("Location: students.php?msg=Invalid Enrollment");
    exit;
}

// Check if certificate already issued
$check = $pdo->prepare("SELECT id FROM certificates WHERE enrollment_id = ?");
$check->execute([$enrollment_id]);
if ($check->fetch()) {
    header("Location: view_student.php?id=" . $enrollment['student_id'] . "&msg=Certificate already issued");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cert_no = "THS-" . date('Y') . "-" . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    try {
        $stmt = $pdo->prepare("INSERT INTO certificates (enrollment_id, certificate_no, issued_date) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$enrollment_id, $cert_no]);

        header("Location: view_student.php?id=" . $enrollment['student_id'] . "&msg=Certificate Issued Successfully");
        exit;
    } catch (PDOException $e) {
        $error = "Error issuing certificate: " . $e->getMessage();
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Issue Certificate</h2>
            <p class="text-muted">Generate a completion certificate for the student.</p>
        </div>
        <a href="view_student.php?id=<?php echo $enrollment['student_id']; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Profile
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="stat-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="text-center mb-4">
                    <div class="avatar mx-auto bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-3"
                        style="width: 70px; height: 70px; font-size: 1.5rem; font-weight: bold;">
                        <i class="fas fa-award"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Confirm Issuance</h5>
                    <p class="text-muted small">Review the details below before generating the certificate.</p>
                </div>

                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Student Name</span>
                        <span class="fw-bold"><?php echo $enrollment['full_name']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Course</span>
                        <span class="fw-bold"><?php echo $enrollment['course_name']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Batch</span>
                        <span class="fw-bold"><?php echo $enrollment['batch_name']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Completion Date</span>
                        <span class="fw-bold"><?php echo date('d M Y'); ?></span>
                    </li>
                </ul>

                <form method="POST">
                    <div class="alert alert-info small border-0 bg-opacity-10 mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        The certificate will be immediately available in the student's portal after generation.
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm">
                        GENERATE CERTIFICATE <i class="fas fa-certificate ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>