<?php
require_once '../includes/auth.php';
checkAuth('teacher');
require_once '../config/db.php';

$pageTitle = "Grade Submissions";
$activePage = "assignments";

$assignment_id = $_GET['id'] ?? 0;

// Verify this assignment belongs to this teacher
$verify = $pdo->prepare("SELECT a.*, b.batch_name FROM assignments a JOIN batches b ON a.batch_id = b.id WHERE a.id = ? AND b.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)");
$verify->execute([$assignment_id, $_SESSION['user_id']]);
$assignment = $verify->fetch();

if (!$assignment) {
    header("Location: assignments.php?error=Invalid Assignment");
    exit;
}

// Handle Grading
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grade_submission'])) {
    $submission_id = $_POST['submission_id'];
    $marks = $_POST['marks'];
    $feedback = $_POST['feedback'];
    $status = 'reviewed';

    $stmt = $pdo->prepare("UPDATE submissions SET marks = ?, feedback = ?, status = ? WHERE id = ?");
    $stmt->execute([$marks, $feedback, $status, $submission_id]);
    
    header("Location: submissions.php?id=$assignment_id&msg=Graded Successfully");
    exit;
}

// Fetch Submissions
$submissions = $pdo->prepare("SELECT s.*, u.full_name, st.enrollment_no 
                              FROM submissions s 
                              JOIN students st ON s.student_id = st.id 
                              JOIN users u ON st.user_id = u.id 
                              WHERE s.assignment_id = ? ORDER BY s.submitted_at DESC");
$submissions->execute([$assignment_id]);
$submissions = $submissions->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Grade Submissions</h2>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($assignment['title']); ?> (<?php echo $assignment['batch_name']; ?>)</p>
        </div>
        <a href="assignments.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <div class="stat-card">
        <?php if (empty($submissions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold">No Submissions Yet</h5>
                <p class="text-muted">Students have not submitted their work yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Student</th>
                            <th class="border-0">Files/Links</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Marks</th>
                            <th class="border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $s): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                <div class="small text-muted"><?php echo date('d M, H:i', strtotime($s['submitted_at'])); ?></div>
                            </td>
                            <td>
                                <?php if ($s['file_path']): ?>
                                    <a href="../<?php echo $s['file_path']; ?>" target="_blank" class="btn btn-sm btn-light me-1" title="Download File"><i class="fas fa-file-download text-primary"></i></a>
                                <?php endif; ?>
                                <?php if ($s['github_link']): ?>
                                    <a href="<?php echo htmlspecialchars($s['github_link']); ?>" target="_blank" class="btn btn-sm btn-light" title="View Link"><i class="fas fa-link text-info"></i></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['status'] == 'submitted'): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill">Needs Grading</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Graded</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-success"><?php echo $s['marks'] !== null ? $s['marks'].'/100' : '-'; ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#gradeModal<?php echo $s['id']; ?>">Grade</button>
                            </td>
                        </tr>

                        <!-- Grade Modal -->
                        <div class="modal fade" id="gradeModal<?php echo $s['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Grade Student</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4 text-start">
                                        <p><strong>Student:</strong> <?php echo htmlspecialchars($s['full_name']); ?></p>
                                        <input type="hidden" name="submission_id" value="<?php echo $s['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Marks (out of 100)</label>
                                            <input type="number" name="marks" class="form-control" max="100" min="0" value="<?php echo $s['marks'] ?? ''; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Feedback</label>
                                            <textarea name="feedback" class="form-control" rows="3" required><?php echo htmlspecialchars($s['feedback'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="submit" name="grade_submission" class="btn btn-success w-100">Save Grade</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
