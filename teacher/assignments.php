<?php
require_once '../includes/auth.php';
checkAuth('teacher');
require_once '../config/db.php';

$pageTitle = "Assignment Management";
$activePage = "assignments";

$userId = $_SESSION['user_id'];
$teacher = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$teacher->execute([$userId]);
$teacherId = $teacher->fetchColumn();

// Fetch Batches for dropdown
$batches = $pdo->prepare("SELECT id, batch_name FROM batches WHERE teacher_id = ?");
$batches->execute([$teacherId]);
$batchList = $batches->fetchAll();

// Handle New Assignment
if (isset($_POST['add_assignment'])) {
    $batch_id = $_POST['batch_id'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $deadline = $_POST['deadline'];
    
    // File upload logic (simplified)
    $file_path = "";
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file_path = "uploads/assignments/" . time() . "_" . $_FILES['file']['name'];
        move_uploaded_file($_FILES['file']['tmp_name'], "../" . $file_path);
    }

    $stmt = $pdo->prepare("INSERT INTO assignments (batch_id, title, description, file_path, deadline) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$batch_id, $title, $desc, $file_path, $deadline]);
    header("Location: assignments.php?msg=Assignment Created");
    exit;
}

$assignments = $pdo->prepare("SELECT a.*, b.batch_name FROM assignments a JOIN batches b ON a.batch_id = b.id WHERE b.teacher_id = ? ORDER BY a.created_at DESC");
$assignments->execute([$teacherId]);
$assignmentList = $assignments->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Assignments</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal"><i class="fas fa-plus me-2"></i>New Assignment</button>
    </div>

    <div class="row g-4">
        <?php if (empty($assignmentList)): ?>
            <div class="col-12 text-center py-5 text-muted">No assignments created.</div>
        <?php else: ?>
            <?php foreach ($assignmentList as $a): ?>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?php echo $a['batch_name']; ?></span>
                        <span class="small text-muted"><i class="fas fa-clock me-1"></i> Due: <?php echo date('d M, H:i', strtotime($a['deadline'])); ?></span>
                    </div>
                    <h5 class="fw-bold"><?php echo $a['title']; ?></h5>
                    <p class="text-muted small"><?php echo $a['description']; ?></p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="submissions.php?id=<?php echo $a['id']; ?>" class="btn btn-light btn-sm px-3">View Submissions</a>
                        <?php if ($a['file_path']): ?>
                            <a href="../<?php echo $a['file_path']; ?>" class="text-primary small fw-bold" download><i class="fas fa-paperclip me-1"></i>Attachment</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addAssignmentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Post New Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Batch</label>
                            <select name="batch_id" class="form-select" required>
                                <?php foreach ($batchList as $b): ?>
                                    <option value="<?php echo $b['id']; ?>"><?php echo $b['batch_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Assignment Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description / Instructions</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Deadline</label>
                            <input type="datetime-local" name="deadline" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Attachment (PDF/Image)</label>
                            <input type="file" name="file" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" name="add_assignment" class="btn btn-primary w-100">Post Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
