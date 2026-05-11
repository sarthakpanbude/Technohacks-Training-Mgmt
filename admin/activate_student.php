<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    header("Location: enrollment_review.php");
    exit;
}

// Fetch student details
$stmt = $pdo->prepare("SELECT s.*, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: enrollment_review.php?error=Student not found");
    exit;
}

// Handle Form Submit
if (isset($_POST['activate'])) {
    try {
        $stmt = $pdo->prepare("UPDATE students SET admission_status = 'active' WHERE id = ?");
        $stmt->execute([$student_id]);
        
        header("Location: enrollment_review.php?success=Student activated successfully!");
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Activate Student";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Verify & Activate</h3>
            <p class="text-muted small">Confirm and activate <?php echo htmlspecialchars($student['full_name']); ?>'s enrollment</p>
        </div>
        <a href="enrollment_review.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="text-center mb-4">
                    <div class="avatar-lg bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                    <p class="text-muted small">Enrollment ID: <?php echo $student['enrollment_no']; ?></p>
                    <hr class="my-4 opacity-25">
                </div>

                <div class="mb-4">
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Course:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['course']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Joining Date:</span>
                            <span class="fw-bold"><?php echo date('d M Y', strtotime($student['created_at'])); ?></span>
                        </div>
                    </div>
                    <p class="small text-muted text-center mb-0">By clicking activate, the student will be granted full access to the portal and their admission will be marked as complete.</p>
                </div>

                <form method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <div class="d-grid">
                        <button type="submit" name="activate" class="btn btn-success btn-lg rounded-pill py-3 fw-bold shadow">
                            Confirm & Activate Student <i class="fas fa-check-circle ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
