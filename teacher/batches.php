<?php
require_once '../includes/auth.php';
checkAuth('teacher');
require_once '../config/db.php';

$pageTitle = "My Batches";
$activePage = "batches";

$teacher = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$teacher->execute([$_SESSION['user_id']]);
$teacher = $teacher->fetch();
$teacher_id = $teacher['id'] ?? 0;

$batches = $pdo->prepare("SELECT b.*, c.name as course_name, (SELECT COUNT(*) FROM enrollments e WHERE e.batch_id = b.id AND e.status = 'active') as student_count FROM batches b JOIN courses c ON b.course_id = c.id WHERE b.teacher_id = ? ORDER BY b.status, b.start_date DESC");
$batches->execute([$teacher_id]);
$batches = $batches->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <h2 class="fw-bold mb-4">My Batches</h2>

    <div class="row g-4">
        <?php if (empty($batches)): ?>
            <div class="col-12 text-center py-5 text-muted">No batches assigned yet.</div>
        <?php else: ?>
            <?php foreach ($batches as $b): ?>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill text-capitalize"><?php echo $b['status']; ?></span>
                        <span class="small text-muted"><?php echo $b['schedule']; ?></span>
                    </div>
                    <h5 class="fw-bold"><?php echo $b['batch_name']; ?></h5>
                    <p class="text-muted small"><?php echo $b['course_name']; ?></p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="small"><i class="fas fa-users me-1"></i><?php echo $b['student_count']; ?> / <?php echo $b['capacity']; ?> Students</span>
                        <span class="small text-muted"><?php echo $b['start_date'] ? date('M d, Y', strtotime($b['start_date'])) : ''; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
