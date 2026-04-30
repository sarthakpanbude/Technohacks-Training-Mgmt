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

$success_msg = $_SESSION['success_msg'] ?? "Admission Processed Successfully!";
// unset($_SESSION['success_msg']); // Keep it for display

$pageTitle = "Admission Successful";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-0" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">
    <?php include '../includes/topbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Premium Success Card -->
                <div class="glass-card animate__animated animate__fadeInUp">
                    <div class="card-header-gradient p-5 text-center text-white">
                        <div class="success-icon-container mb-4">
                            <div class="pulse-ring"></div>
                            <i class="fas fa-graduation-cap fa-3x"></i>
                        </div>
                        <h2 class="fw-bold mb-0">Admission Confirmed</h2>
                        <p class="opacity-75 mb-0">TechnoHacks Elite Training System</p>
                    </div>
                    
                    <div class="card-body p-5">
                        <div class="message-box p-4 rounded-4 mb-4 shadow-sm bg-white border-start border-primary border-4">
                            <?php 
                            // Convert the structured text into a more readable HTML format
                            $lines = explode("\n", $success_msg);
                            foreach ($lines as $line) {
                                if (trim($line) == "━━━━━━━━━━━━━━━━━━━") {
                                    echo "<hr class='my-3 opacity-10'>";
                                } elseif (strpos($line, "🎓") !== false) {
                                    echo "<h4 class='fw-bold text-primary mb-3'>" . $line . "</h4>";
                                } elseif (strpos($line, "🚀") !== false) {
                                    echo "<p class='lead fw-medium mb-4'>" . $line . "</p>";
                                } elseif (strpos($line, "🧾") !== false || strpos($line, "💰") !== false) {
                                    echo "<div class='d-flex justify-content-between mb-2 small fw-bold text-dark'>" . $line . "</div>";
                                } else {
                                    echo "<p class='mb-2 text-muted'>" . $line . "</p>";
                                }
                            }
                            ?>
                        </div>

                        <div class="process-steps mb-5">
                            <div class="step-item d-flex align-items-center mb-3">
                                <div class="step-icon bg-success-light text-success me-3">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="fw-medium text-dark">Enrollment Activated</span>
                            </div>
                            <div class="step-item d-flex align-items-center mb-3">
                                <div class="step-icon bg-success-light text-success me-3">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="fw-medium text-dark">Fees Calculated & Receipt Generated</span>
                            </div>
                            <div class="step-item d-flex align-items-center mb-3">
                                <div class="step-icon bg-success-light text-success me-3">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="fw-medium text-dark">WhatsApp Notification Sent</span>
                            </div>
                        </div>

                        <div class="d-grid gap-3">
                            <a href="enrollment_review.php" class="btn btn-primary btn-lg rounded-pill shadow-lg py-3 fw-bold">
                                ✨ Let's Start Your Journey <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                            <div class="d-flex gap-2">
                                <a href="generate_form.php?id=<?php echo htmlspecialchars($student_id); ?>" target="_blank" class="btn btn-outline-primary rounded-pill flex-grow-1">
                                    <i class="fas fa-file-alt me-2"></i> Print Form
                                </a>
                                <a href="generate_receipt.php?id=<?php echo htmlspecialchars($student_id); ?>" target="_blank" class="btn btn-outline-secondary rounded-pill flex-grow-1">
                                    <i class="fas fa-print me-2"></i> Receipt
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill flex-grow-1">
                                    <i class="fas fa-home me-2"></i> Home
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border-radius: 30px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.card-header-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
}

.success-icon-container {
    position: relative;
    z-index: 1;
    width: 100px;
    height: 100px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pulse-ring {
    position: absolute;
    width: 100px;
    height: 100px;
    border: 5px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.bg-success-light {
    background-color: #dcfce7;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.message-box p {
    line-height: 1.6;
}

.btn-lg {
    transition: all 0.3s ease;
}

.btn-lg:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.animate__fadeInUp {
    animation-duration: 0.8s;
}
</style>

<?php include '../includes/footer.php'; ?>
