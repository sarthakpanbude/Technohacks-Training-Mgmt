<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "My Certificates";
$activePage = "certificates";

$userId = $_SESSION['user_id'];
// Get student ID
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();
$studentId = $student['id'];

// Fetch certificates
$stmt = $pdo->prepare("SELECT c.*, co.name as course_name, b.batch_name 
                       FROM certificates c 
                       JOIN enrollments e ON c.enrollment_id = e.id 
                       JOIN batches b ON e.batch_id = b.id 
                       JOIN courses co ON b.course_id = co.id 
                       WHERE e.student_id = ? 
                       ORDER BY c.issued_date DESC");
$stmt->execute([$studentId]);
$certificates = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">My Certificates</h2>
            <p class="text-muted">View and download your earned completion certificates.</p>
        </div>
    </div>

    <?php if (empty($certificates)): ?>
        <div class="stat-card text-center py-5">
            <i class="fas fa-award fa-3x text-warning mb-3"></i>
            <h5 class="fw-bold">No Certificates Yet</h5>
            <p class="text-muted">Complete your courses and exams to earn certificates!</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($certificates as $cert): ?>
            <div class="col-md-6 col-lg-4">
                <div class="stat-card h-100 border-top border-4 border-primary">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3"><?php echo $cert['certificate_no']; ?></span>
                        <i class="fas fa-certificate text-warning"></i>
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo $cert['course_name']; ?></h5>
                    <p class="text-muted small mb-4">Batch: <?php echo $cert['batch_name']; ?></p>
                    
                    <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top">
                        <span class="small text-muted"><?php echo date('d M Y', strtotime($cert['issued_date'])); ?></span>
                        <a href="view_certificate.php?id=<?php echo $cert['id']; ?>" target="_blank" class="btn btn-sm btn-dark rounded-pill px-3">
                            <i class="fas fa-eye me-1"></i> View
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
