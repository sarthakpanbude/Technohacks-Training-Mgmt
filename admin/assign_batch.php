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
if (isset($_POST['assign'])) {
    $batch_id = $_POST['batch_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE students SET admission_status = 'active', batch_id = ? WHERE id = ?");
        $stmt->execute([$batch_id, $student_id]);
        
        // Also add an enrollment record if not exists
        $check = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND batch_id = ?");
        $check->execute([$student_id, $batch_id]);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, batch_id, status) VALUES (?, ?, 'active')");
            $stmt->execute([$student_id, $batch_id]);
        }
        
        header("Location: enrollment_review.php?success=Student assigned to batch and activated!");
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Assign Batch";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Assign Batch</h3>
            <p class="text-muted small">Select a batch for <?php echo htmlspecialchars($student['full_name']); ?></p>
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
                    <div class="avatar-lg bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                    <p class="text-muted small"><?php echo $student['enrollment_no']; ?></p>
                </div>

                <form method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Select Batch</label>
                        <select name="batch_id" class="form-select rounded-3 p-3 shadow-sm" required>
                            <option value="">Choose a Batch...</option>
                            <?php
                            $batches = $pdo->query("SELECT b.id, b.batch_name, c.course_name, b.schedule, b.start_time FROM batches b LEFT JOIN courses c ON b.course_id = c.id WHERE b.status IN ('upcoming', 'active')")->fetchAll();
                            foreach ($batches as $b) {
                                $course = $b['course_name'] ?: 'General';
                                $timing = $b['start_time'] ? " at " . $b['start_time'] : "";
                                echo "<option value='{$b['id']}'>{$b['batch_name']} ({$course}) - {$b['schedule']}{$timing}</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text small mt-2">The student will be marked as <strong>ACTIVE</strong> upon assignment.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="assign" class="btn btn-primary btn-lg rounded-pill py-3 fw-bold shadow">
                            Assign & Activate Student <i class="fas fa-check-circle ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
