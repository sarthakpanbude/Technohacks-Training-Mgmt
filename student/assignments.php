<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "My Assignments";
$activePage = "assignments";

$userId = $_SESSION['user_id'];
$student = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$student->execute([$userId]);
$studentId = $student->fetchColumn();

// Get Enrollment/Batch
$enrollment = $pdo->prepare("SELECT batch_id FROM enrollments WHERE student_id = ? AND status = 'active'");
$enrollment->execute([$studentId]);
$batchId = $enrollment->fetchColumn();

// Handle Submission
if (isset($_POST['submit_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $github = $_POST['github_link'];
    
    $file_path = "";
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file_path = "uploads/submissions/" . time() . "_" . $_FILES['file']['name'];
        move_uploaded_file($_FILES['file']['tmp_name'], "../" . $file_path);
    }

    $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, github_link, status) VALUES (?, ?, ?, ?, 'submitted')");
    $stmt->execute([$assignment_id, $studentId, $file_path, $github]);
    header("Location: assignments.php?msg=Submitted Successfully");
    exit;
}

// Fetch Assignments for this student's batch
$assignments = [];
if ($batchId) {
    $stmt = $pdo->prepare("SELECT a.*, (SELECT status FROM submissions WHERE assignment_id = a.id AND student_id = ?) as submission_status FROM assignments a WHERE a.batch_id = ? ORDER BY a.deadline ASC");
    $stmt->execute([$studentId, $batchId]);
    $assignments = $stmt->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Assignments</h2>
    </div>

    <div class="row g-4">
        <?php if (empty($assignments)): ?>
            <div class="col-12 text-center py-5 text-muted">No assignments assigned to your batch yet.</div>
        <?php else: ?>
            <?php foreach ($assignments as $a): ?>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-2">
                        <?php if ($a['submission_status']): ?>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill text-capitalize"><?php echo $a['submission_status']; ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill">Pending</span>
                        <?php endif; ?>
                        <span class="small text-muted"><i class="fas fa-clock me-1"></i> Due: <?php echo date('d M, H:i', strtotime($a['deadline'])); ?></span>
                    </div>
                    <h5 class="fw-bold"><?php echo $a['title']; ?></h5>
                    <p class="text-muted small"><?php echo $a['description']; ?></p>
                    
                    <div class="mt-3">
                        <?php if (!$a['submission_status']): ?>
                            <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#submitModal<?php echo $a['id']; ?>">Submit Now</button>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary btn-sm w-100" disabled>Already Submitted</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="submitModal<?php echo $a['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Submit Assignment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                                <div class="modal-body p-4">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">GitHub Repository Link (Optional)</label>
                                        <input type="url" name="github_link" class="form-control" placeholder="https://github.com/...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Upload File (ZIP/PDF)</label>
                                        <input type="file" name="file" class="form-control">
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="submit" name="submit_assignment" class="btn btn-primary w-100">Submit Work</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
