<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$student_id = $_GET['id'] ?? null;
if (!$student_id) exit("Student ID missing");

$stmt = $pdo->prepare("SELECT sb.*, sf.* FROM students_basic sb JOIN student_fees sf ON sb.student_id = sf.student_id WHERE sb.student_id = ?");
$stmt->execute([$student_id]);
$data = $stmt->fetch();

if (!$data) exit("Student not found");

$pageTitle = "Admission Successful";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="text-center py-5">
        <div class="mb-4">
            <i class="fas fa-check-circle text-success fa-5x"></i>
        </div>
        <h2 class="fw-bold mb-2">Admission Confirmed!</h2>
        <p class="text-muted mb-4">Student ID: <span class="fw-bold text-primary"><?php echo $student_id; ?></span></p>
        
        <div class="row justify-content-center g-3">
            <div class="col-md-3">
                <div class="stat-card p-4 bg-white rounded-4 shadow-sm border-0">
                    <i class="fas fa-file-pdf fa-2x text-danger mb-3"></i>
                    <h6 class="fw-bold">Application Form</h6>
                    <a href="generate_form.php?id=<?php echo $student_id; ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100 rounded-pill mt-2">Download PDF</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card p-4 bg-white rounded-4 shadow-sm border-0">
                    <i class="fas fa-receipt fa-2x text-primary mb-3"></i>
                    <h6 class="fw-bold">Fee Receipt</h6>
                    <a href="generate_receipt.php?id=<?php echo $student_id; ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-2">Download PDF</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card p-4 bg-white rounded-4 shadow-sm border-0">
                    <i class="fab fa-whatsapp fa-2x text-success mb-3"></i>
                    <h6 class="fw-bold">Share via WhatsApp</h6>
                    <a href="https://wa.me/<?php echo $data['student_id']; ?>?text=Congratulations! Your admission is confirmed. Download your receipt: <?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/generate_receipt.php?id=' . $student_id; ?>" target="_blank" class="btn btn-sm btn-success w-100 rounded-pill mt-2">Send Message</a>
                </div>
            </div>
        </div>
        
        <div class="mt-5">
            <a href="dashboard.php" class="btn btn-primary px-5 rounded-pill">Return to Dashboard</a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
