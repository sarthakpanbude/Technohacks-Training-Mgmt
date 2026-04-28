<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "Study Materials & Learning";
$activePage = "learning";

$student_id = $pdo->query("SELECT id FROM students WHERE user_id = " . $_SESSION['user_id'])->fetchColumn();

// Get the student's active batches
$active_batches = $pdo->prepare("SELECT b.id, b.batch_name, c.name as course_name FROM enrollments e JOIN batches b ON e.batch_id = b.id JOIN courses c ON b.course_id = c.id WHERE e.student_id = ? AND e.status = 'active'");
$active_batches->execute([$student_id]);
$batches = $active_batches->fetchAll();

// Get the materials for those batches
$materials = [];
if (!empty($batches)) {
    $batch_ids = array_column($batches, 'id');
    $placeholders = implode(',', array_fill(0, count($batch_ids), '?'));
    $mat_stmt = $pdo->prepare("SELECT m.*, t.full_name as teacher_name FROM study_materials m JOIN teachers tc ON m.teacher_id = tc.id JOIN users t ON tc.user_id = t.id WHERE m.batch_id IN ($placeholders) ORDER BY m.uploaded_at DESC");
    $mat_stmt->execute($batch_ids);
    $materials = $mat_stmt->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <h2 class="fw-bold mb-4">Study Materials & Notes</h2>

    <?php if (empty($batches)): ?>
        <div class="stat-card text-center py-5">
            <i class="fas fa-lock fa-3x text-muted mb-3"></i>
            <h5 class="fw-bold">No Active Enrollments</h5>
            <p class="text-muted">You are not enrolled in any active batches. Study materials will appear here once you are assigned to a batch.</p>
        </div>
    <?php elseif (empty($materials)): ?>
        <div class="stat-card text-center py-5">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <h5 class="fw-bold">No Materials Found</h5>
            <p class="text-muted">Your teachers have not uploaded any study materials for your batches yet.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($materials as $m): ?>
            <div class="col-md-4">
                <div class="stat-card h-100 border-top border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill">
                            <?php 
                                foreach($batches as $b) {
                                    if($b['id'] == $m['batch_id']) echo htmlspecialchars($b['course_name']);
                                }
                            ?>
                        </span>
                        <a href="../uploads/materials/<?php echo $m['file_path']; ?>" class="text-primary fs-5" target="_blank" download title="Download"><i class="fas fa-cloud-download-alt"></i></a>
                    </div>
                    <h5 class="fw-bold text-truncate" title="<?php echo htmlspecialchars($m['title']); ?>">
                        <?php echo htmlspecialchars($m['title']); ?>
                    </h5>
                    <p class="text-muted small mb-3" style="min-height: 40px;"><?php echo htmlspecialchars($m['description'] ?? 'No description provided.'); ?></p>
                    
                    <div class="d-flex align-items-center mt-auto border-top pt-3 small text-muted">
                        <i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($m['teacher_name']); ?>
                        <span class="ms-auto"><i class="fas fa-clock me-1"></i><?php echo date('d M Y', strtotime($m['uploaded_at'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
