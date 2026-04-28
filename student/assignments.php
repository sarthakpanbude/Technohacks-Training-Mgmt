<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "My Assignments";
$activePage = "assignments";

$student_id = $pdo->query("SELECT id FROM students WHERE user_id = " . $_SESSION['user_id'])->fetchColumn();

// Get active batches
$active_batches = $pdo->prepare("SELECT batch_id FROM enrollments WHERE student_id = ? AND status = 'active'");
$active_batches->execute([$student_id]);
$batch_ids = $active_batches->fetchAll(PDO::FETCH_COLUMN);

// Handle Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $github_link = $_POST['github_link'] ?? null;
    $file_path = null;

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
        $new_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest = '../uploads/submissions/' . $new_name;
        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $dest)) {
            $file_path = 'uploads/submissions/' . $new_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, github_link, status) VALUES (?, ?, ?, ?, 'submitted') ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), github_link = VALUES(github_link), status = 'submitted'");
    $stmt->execute([$assignment_id, $student_id, $file_path, $github_link]);
    
    header("Location: assignments.php?msg=Assignment Submitted!");
    exit;
}

$assignments = [];
if (!empty($batch_ids)) {
    $placeholders = implode(',', array_fill(0, count($batch_ids), '?'));
    $query = "SELECT a.*, c.name as course_name, s.status as sub_status, s.marks, s.feedback 
              FROM assignments a 
              JOIN batches b ON a.batch_id = b.id 
              JOIN courses c ON b.course_id = c.id 
              LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = $student_id 
              WHERE a.batch_id IN ($placeholders) ORDER BY a.deadline ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($batch_ids);
    $assignments = $stmt->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Assignments</h2>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (empty($assignments)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold">No Assignments Found</h5>
                <p class="text-muted">You have no pending assignments right now.</p>
            </div>
        <?php else: ?>
            <?php foreach ($assignments as $a): 
                $is_past = strtotime($a['deadline']) < time();
                $status_color = 'warning';
                $status_text = 'Pending';
                
                if ($a['sub_status'] == 'submitted') { $status_color = 'primary'; $status_text = 'Submitted'; }
                if ($a['sub_status'] == 'reviewed' || $a['sub_status'] == 'approved') { $status_color = 'success'; $status_text = 'Graded'; }
            ?>
            <div class="col-md-6">
                <div class="stat-card h-100 border-start border-4 border-<?php echo $status_color; ?>">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-<?php echo $status_color; ?> bg-opacity-10 text-<?php echo $status_color; ?> rounded-pill"><?php echo $status_text; ?></span>
                        <span class="small <?php echo $is_past ? 'text-danger fw-bold' : 'text-muted'; ?>">
                            <i class="fas fa-clock me-1"></i> Due: <?php echo date('d M Y, h:i A', strtotime($a['deadline'])); ?>
                        </span>
                    </div>
                    <h5 class="fw-bold"><?php echo htmlspecialchars($a['title']); ?></h5>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($a['description']); ?></p>
                    
                    <?php if ($a['file_path']): ?>
                        <a href="../<?php echo $a['file_path']; ?>" class="btn btn-sm btn-outline-secondary mb-3" target="_blank" download><i class="fas fa-download me-2"></i>Download Resource</a>
                    <?php endif; ?>

                    <?php if ($a['sub_status'] == 'reviewed' || $a['sub_status'] == 'approved'): ?>
                        <div class="bg-light p-3 rounded mt-2">
                            <p class="mb-1"><strong>Marks:</strong> <span class="text-success fw-bold"><?php echo $a['marks']; ?>/100</span></p>
                            <p class="mb-0 small text-muted"><strong>Feedback:</strong> <?php echo htmlspecialchars($a['feedback']); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="mt-auto border-top pt-3">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                                <div class="mb-2">
                                    <input type="text" name="github_link" class="form-control form-control-sm" placeholder="GitHub/Project Link (Optional)">
                                </div>
                                <div class="d-flex gap-2">
                                    <input type="file" name="submission_file" class="form-control form-control-sm" <?php echo $is_past ? 'disabled' : ''; ?>>
                                    <button type="submit" name="submit_assignment" class="btn btn-sm btn-primary px-4" <?php echo $is_past ? 'disabled' : ''; ?>>Submit</button>
                                </div>
                                <?php if ($is_past): ?><small class="text-danger d-block mt-1">Submission closed.</small><?php endif; ?>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
